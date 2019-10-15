<?php

namespace Unlooped\GridBundle\Service;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\QueryBuilder;
use Knp\Component\Pager\PaginatorInterface;
use ReflectionClass;
use ReflectionException;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\Routing\RouterInterface;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Unlooped\GridBundle\Entity\Filter;
use Unlooped\GridBundle\Entity\FilterRow;
use Unlooped\GridBundle\FilterType\FilterType;
use Unlooped\GridBundle\Form\FilterFormType;
use Unlooped\GridBundle\Helper\GridHelper;
use Unlooped\GridBundle\Model\Grid;
use Unlooped\Helper\StringHelper;

class GridService
{
    /** @var RequestStack */
    private $requestStack;
    /** @var PaginatorInterface */
    private $paginator;
    /** @var FormFactoryInterface */
    private $formFactory;
    private $em;
    private $saveFilter;
    private $useRouteInFilterReference;
    private $filterRepo;
    private $flashBag;
    private $templating;
    private $router;

    public function __construct(
        RequestStack $requestStack,
        PaginatorInterface $paginator,
        FormFactoryInterface $formFactory,
        EntityManager $em,
        FlashBagInterface $flashBag,
        Environment $templating,
        RouterInterface $router,
        bool $saveFilter,
        bool $useRouteInFilterReference
    )
    {
        $this->requestStack = $requestStack;
        $this->paginator = $paginator;
        $this->formFactory = $formFactory;
        $this->em = $em;
        $this->saveFilter = $saveFilter;
        $this->useRouteInFilterReference = $useRouteInFilterReference;
        $this->flashBag = $flashBag;
        $this->templating = $templating;
        $this->router = $router;
        $this->filterRepo = $em->getRepository(Filter::class);
    }

    /**
     * @param string $className
     * @param array $options
     * @return GridHelper
     * @throws ReflectionException
     * @throws NonUniqueResultException
     */
    public function getGridHelper(string $className, array $options = [], string $filterHash = null): GridHelper
    {
        $reflect = new ReflectionClass($className);
        $alias = StringHelper::first($reflect->getShortName(), 1)->toLowerCase()->toString();

        /** @var ServiceEntityRepository $repo */
        $repo = $this->em->getRepository($className);
        $qb = $repo->createQueryBuilder($alias);

        $filter = $this->getFilter($className, $filterHash);

        return GridHelper::create($qb, $options, $filter);
    }

    /**
     * @throws NonUniqueResultException
     */
    protected function getFilter(string $className, ?string $filterHash = null): Filter
    {
        if ($filterHash && $filter = $this->filterRepo->findOneByHash($filterHash)) {
            return $filter;
        }

        $request = $this->requestStack->getCurrentRequest();
        if ($this->saveFilter
            && $request
            && $request->get('_route')
            && $filter = $this->filterRepo->findDefaultForRoute($request->get('_route'))
        ) {
            return $filter;
        }

        $filter = new Filter();
        $filter->setEntity($className);

        if ($request) {
            $filter->setRoute(str_replace('.filter', '', $request->get('_route')));
        }

        return $filter;
    }

    /**
     * @throws NonUniqueResultException
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function getGrid(GridHelper $gridHelper): Grid
    {
        $request = $this->requestStack->getCurrentRequest();
        $filterAllowedToSave = $this->saveFilter && $gridHelper->getAllowSaveFilter();

        $filter = $gridHelper->getFilter();
        $filter->setIsSaveable($filterAllowedToSave);

        $form = $this->formFactory->create(FilterFormType::class, $filter, [
            'fields' => $filter->getFields(),
            'filters' => $gridHelper->getFilters(),
            'method' => 'get',
        ]);

        $form->handleRequest($request);

        $filterApplied = false;
        $filterSaved = false;
        $filterDeleted = false;

        if ($filter->getHash() || $filter->hasDefaultShowFilter() || ($form->isSubmitted() && $form->isValid())) {
            $filterApplied = $filter->getHash() || ($form->isSubmitted() && $form->isValid());
            $qb = $gridHelper->getQueryBuilder();

            $this->handleFilter($qb, $filter, $gridHelper);

            if ($filterAllowedToSave && $form->get('filter_and_save')->isClicked()) {
                $this->saveFilter($filter);
                $filterSaved = true;
            }

            if ($filterAllowedToSave && $form->has('delete_filter') && $form->get('delete_filter')->isClicked()) {
                $this->deleteFilter($filter);
                $filterDeleted = true;
            }

            if ($filter->getHash()) {
                if ($form->has('remove_default') && $form->get('remove_default')->isClicked()) {
                    $this->removeFilterAsDefault($filter);
                    $filterSaved = true;
                } else if ($form->has('make_default') && $form->get('make_default')->isClicked()) {
                    $this->makeFilterAsDefault($filter);
                    $filterSaved = true;
                }
            }
        }

        if ($request) {
            $currentPage = $request->query->getInt($gridHelper->getPageParameterName(), $gridHelper->getDefaultPage());
            $currentPerPage = $request->query->getInt($gridHelper->getPerPageParameterName(), $gridHelper->getPerPage());
        } else {
            $currentPage = 1;
            $currentPerPage = 1;
        }

        $pagination = $this->paginator->paginate(
            $gridHelper->getQueryBuilder(),
            $currentPage,
            $currentPerPage,
            [
                'wrap-queries' => $gridHelper->getWrapQueries(),
                'distinct'     => $gridHelper->getDistinctQuery(),
            ]
        );

        $existingFilters = $this->filterRepo->findByRoute(str_replace('.filter', '', $request->get('_route')));

        $filterData = $this->getFilterData($gridHelper);

        return new Grid(
            $gridHelper,
            $pagination,
            $form,
            $currentPage,
            $currentPerPage,
            $filterData,
            $filterAllowedToSave,
            $filterApplied,
            $filterSaved,
            $filterDeleted,
            $request->get('_route'),
            $request->get('_route_params'),
            $existingFilters
        );
    }

    public function handleFilter(QueryBuilder $qb, Filter $filter, GridHelper $gridHelper): void
    {

        foreach ($filter->getRows() as $row) {
            $filterType = $gridHelper->getFilterTypeForField($row->getField());
            $filterType->handleFilter($qb, $row);
        }
    }

    public function getFilterData(GridHelper $gh)
    {
        $filters = $gh->getFilters();
        $filterData = [];
        foreach ($filters as $field => $filterType) {
            $filterData[$field] = [
                'operators' => $filterType::getAvailableOperators(),
                'type' => get_class($filterType),
                'options' => $filterType->getOptions(),
                'template' => $this->getFilterTemplateForFilter($filterType),
                'templatePath' => $filterType->getTemplate(),
            ];
        }

        return $filterData;
    }

    /**
     * @throws NonUniqueResultException
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function saveFilter(Filter $filter): void
    {
        $existingFilter = $this->doesSameFilterExist($filter);
        if ($existingFilter && $existingFilter->getId() !== $filter->getId()) {
            $this->flashBag->add('unlooped_grid.alert', 'Filter already Exists: ' . $existingFilter->getName());
            return;
        }

        $filter->setHash($this->getHashForFilter($filter));

        $this->em->persist($filter);
        $this->em->flush();
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function deleteFilter(Filter $filter): void
    {
        $this->em->remove($filter);
        $this->em->flush();
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function makeFilterAsDefault(Filter $filter): void
    {
        $defaultFilter = $this->filterRepo->findDefaultForRoute($filter->getRoute());
        if ($defaultFilter) {
            $defaultFilter->setIsDefault(false);
            $this->em->persist($defaultFilter);
        }

        $filter->setIsDefault(true);

        $this->em->persist($filter);
        $this->em->flush();
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function removeFilterAsDefault(Filter $filter): void
    {
        $filter->setIsDefault(false);

        $this->em->persist($filter);
        $this->em->flush();
    }

    /**
     * @throws NonUniqueResultException
     */
    public function doesSameFilterExist(Filter $filter): ?Filter
    {
        $hash = $this->getHashForFilter($filter);

        return $this->filterRepo->findOneByHash($hash);
    }

    public function getHashForFilter(Filter $filter)
    {
        $arr = [];
        if ($route = $filter->getRoute()) {
            $arr[] = $route;
        }

        foreach ($filter->getRows() as $row) {
            $arr[]= $row->getField() . $row->getOperator() . $row->getValue();
        }

        sort($arr);

        return sha1(implode('-', $arr));
    }

    /**
     * @param FilterType $filterType
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function getFilterTemplateForFilter(FilterType $filterType): string
    {
        $template = $filterType->getTemplate();

        $formBuilder = $this->formFactory->createNamedBuilder('__filterrow__', FormType::class, null, ['csrf_protection' => false]);

        $filterType->preSubmitFormData($formBuilder);

        $form = $formBuilder->getForm();

        $tpl = $this->templating->render($template, [
            'data' => $form->createView(),
        ]);

        return str_replace('__filterrow__', 'filter_form[rows][__name__]', $tpl);
    }

    /**
     * @param string $template #Template
     *
     * @throws NonUniqueResultException
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function render(string $template, GridHelper $gridHelper, array $parameters = []): Response
    {
        $grid = $this->getGrid($gridHelper);
        $baseRoute = str_replace('.filter', '', $grid->getRoute());

        if ($grid->wasFilterSaved()) {
            $filterRoute = $baseRoute . '.filter';
            return $this->redirectToRoute($filterRoute, ['filterHash' => $grid->getFilter()->getHash()]);
        }

        if ($grid->wasFilterDeleted()) {
            return $this->redirectToRoute($baseRoute);
        }

        $gridParameters = [
            'grid' => $grid,
        ];

        $content = $this->templating->render($template, array_merge($parameters, $gridParameters));

        $response = new Response();
        $response->setContent($content);

        return $response;
    }

    /**
     * @param string $route #Route
     */
    protected function redirectToRoute(string $route, array $parameters = [], int $status = 302): RedirectResponse {
        return new RedirectResponse($this->router->generate($route, $parameters), $status);
    }
}
