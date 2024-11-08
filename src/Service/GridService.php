<?php

namespace Unlooped\GridBundle\Service;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\QueryBuilder;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\PaginatorInterface;
use LogicException;
use ReflectionClass;
use ReflectionException;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Unlooped\GridBundle\Column\Registry\ColumnRegistry;
use Unlooped\GridBundle\ColumnType\NumberColumn;
use Unlooped\GridBundle\Entity\Filter as FilterEntity;
use Unlooped\GridBundle\Entity\FilterUserSettings;
use Unlooped\GridBundle\Filter\Filter;
use Unlooped\GridBundle\Filter\Registry\FilterRegistry;
use Unlooped\GridBundle\Form\FilterFormType;
use Unlooped\GridBundle\Form\FilterUserSettingsFormType;
use Unlooped\GridBundle\Helper\GridHelper;
use Unlooped\GridBundle\Helper\RelationsHelper;
use Unlooped\GridBundle\Model\FilterFormRequest;
use Unlooped\GridBundle\Model\FilterUserSettingsFormRequest;
use Unlooped\GridBundle\Model\Grid;
use Unlooped\GridBundle\Repository\FilterRepository;
use Unlooped\GridBundle\Repository\FilterUserSettingsRepository;
use Unlooped\GridBundle\Struct\AggregateResultStruct;

use function Symfony\Component\String\u;

class GridService
{
    private RequestStack $requestStack;
    private PaginatorInterface $paginator;
    private FormFactoryInterface $formFactory;
    private EntityManager $em;
    private bool $saveFilter;
    private FilterRepository $filterRepo;
    private Environment $templating;
    private RouterInterface $router;

    private ColumnRegistry $columnRegistry;
    private FilterRegistry $filterRegistry;
    private FilterUserSettingsRepository $filterUserSettingsRepo;
    private TranslatorInterface $translator;
    private string $baseTemplatePath;

    public function __construct(
        RequestStack $requestStack,
        PaginatorInterface $paginator,
        FormFactoryInterface $formFactory,
        EntityManager $em,
        Environment $templating,
        RouterInterface $router,
        ColumnRegistry $columnRegistry,
        FilterRegistry $filterRegistry,
        TranslatorInterface $translator,
        bool $saveFilter,
        string $baseTemplatePath
    ) {
        $this->requestStack           = $requestStack;
        $this->paginator              = $paginator;
        $this->formFactory            = $formFactory;
        $this->em                     = $em;
        $this->saveFilter             = $saveFilter;
        $this->templating             = $templating;
        $this->router                 = $router;
        $this->filterRepo             = $em->getRepository(FilterEntity::class);
        $this->filterUserSettingsRepo = $em->getRepository(FilterUserSettings::class);
        $this->columnRegistry         = $columnRegistry;
        $this->translator             = $translator;
        $this->filterRegistry         = $filterRegistry;
        $this->baseTemplatePath       = $baseTemplatePath;
    }

    /**
     * @throws ReflectionException
     * @throws NonUniqueResultException
     */
    public function getGridHelper(string $className, array $options = [], ?string $filterHash = null): GridHelper
    {
        $reflect = new ReflectionClass($className);
        $alias   = u($reflect->getShortName())->truncate(1)->lower()->toString();

        /** @var ServiceEntityRepository $repo */
        $repo = $this->em->getRepository($className);
        $qb   = $repo->createQueryBuilder($alias);

        $filter = $this->getFilter($className, $filterHash);

        return new GridHelper($qb, $this->columnRegistry, $this->filterRegistry, $options, $filter, $this->baseTemplatePath);
    }

    /**
     * @throws NonUniqueResultException
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws ReflectionException
     */
    public function getGrid(GridHelper $gridHelper): Grid
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            throw new LogicException('No Request Available');
        }

        $qb      = $gridHelper->getQueryBuilder();
        $sort    = (string) $request->query->get('sort');
        $route   = (string) $request->attributes->get('_route');

        $filterFormRequest = $this->handleFilterForm($gridHelper);
        if ($gridHelper->getUserSettingsEnabled()) {
            $filterUserSettingsFormRequest = $this->handleColumnsForm($gridHelper);
        } else {
            $filterUserSettingsFormRequest = null;
        }

        if ($sort && ($col = $gridHelper->getColumnForAlias($sort))) {
            RelationsHelper::joinRequiredPaths($qb, $gridHelper->getFilter()->getEntity(), $col->getField());
        }

        $pagination      = $this->getPagination($gridHelper);
        $existingFilters = $this->filterRepo->findByRoute(str_replace('.filter', '', $route));
        $filterData      = $this->getFilterData($gridHelper);

        $aggregateResults = $this->getAggreagates($gridHelper);

        return new Grid(
            $gridHelper,
            $pagination,
            $filterFormRequest,
            $filterUserSettingsFormRequest,
            $filterData,
            $this->saveFilter && $gridHelper->getAllowSaveFilter(),
            $route,
            array_merge($request->attributes->get('_route_params'), $request->query->all()),
            $existingFilters,
            $aggregateResults
        );
    }

    public function getAggreagates(GridHelper $gridHelper): ?AggregateResultStruct
    {
        $entity = $gridHelper->getFilter()->getEntity();
        $qb     = clone $gridHelper->getQueryBuilder();
        RelationsHelper::cloneAliases($gridHelper->getQueryBuilder(), $qb, $entity);

        $aggregateCount = 0;
        foreach ($gridHelper->getColumns() as $column) {
            $columnType = $column->getType();
            if (!$columnType instanceof NumberColumn) {
                continue;
            }

            $aggregates = [...$column->getOption('aggregates')];
            if ($column->getOption('show_aggregate') && 'callback' !== $column->getOption('show_aggregate')) {
                $aggregates[] = $column->getOption('show_aggregate');
            }
            if (\is_callable($column->getOption('aggregate_callback'))) {
                $aggregates[] = $column->getOption('aggregate_callback');
            }

            if (\count($aggregates) > 0) {
                $fieldInfo = RelationsHelper::joinRequiredPaths($qb, $entity, $column->getField());

                foreach ($aggregates as $aggregate) {
                    if (\is_callable($aggregate)) {
                        $aggrFn = $aggregate($column, $qb);
                    } else {
                        $aggregateAlias = $columnType->getAggregateAlias($aggregate, $column->getField());
                        $aggrFn         = strtoupper($aggregate).'('.$fieldInfo->alias.') AS '.$aggregateAlias;
                    }

                    if (0 === $aggregateCount++) {
                        $qb->select($aggrFn);
                    } else {
                        $qb->addSelect($aggrFn);
                    }
                }
            }
        }

        if ($aggregateCount > 0) {
            return new AggregateResultStruct((object) $qb->getQuery()->getResult()[0]);
        }

        return null;
    }

    public function handleFilter(QueryBuilder $qb, FilterEntity $filterEntity, GridHelper $gridHelper): void
    {
        foreach ($filterEntity->getRows() as $row) {
            $filter = $gridHelper->getFilterTypeForField($row->getField());
            $filter->handleFilter($qb, $row);
        }
    }

    /**
     * @return array<string, array<string, mixed>>
     *
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function getFilterData(GridHelper $helper): array
    {
        $filters    = $helper->getFilters();
        $filterData = [];
        foreach ($filters as $field => $filter) {
            $filterData[$field] = [
                'operators'    => $this->translateValues($filter->getOption('operators', [])),
                'type'         => \get_class($filter->getType()),
                'options'      => $filter->getOptions(),
                'template'     => $this->getFilterTemplateForFilter($filter),
                'templatePath' => $filter->getOption('template'),
            ];
        }

        return $filterData;
    }

    public function translateValues(array $array): array
    {
        $res = [];
        foreach ($array as $key => $item) {
            $res[$key] = $this->translator->trans($item, [], 'unlooped_grid');
        }

        return $res;
    }

    /**
     * @throws NonUniqueResultException
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function saveFilter(FilterEntity $filter): void
    {
        $existingFilter = $this->doesSameFilterExist($filter);
        if ($existingFilter && $existingFilter->getId() !== $filter->getId()) {
            $session = $this->requestStack->getSession();
            if ($session instanceof Session) {
                $session->getFlashBag()->add('unlooped_grid.alert', 'Filter already Exists: '.$existingFilter->getName());
            }

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
    public function deleteFilter(FilterEntity $filter): void
    {
        $this->em->remove($filter);
        $this->em->flush();
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function makeFilterAsDefault(FilterEntity $filter): void
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
    public function removeFilterAsDefault(FilterEntity $filter): void
    {
        $filter->setIsDefault(false);

        $this->em->persist($filter);
        $this->em->flush();
    }

    /**
     * @throws NonUniqueResultException
     */
    public function doesSameFilterExist(FilterEntity $filter): ?FilterEntity
    {
        $hash = $this->getHashForFilter($filter);

        return $this->filterRepo->findOneByHash($hash);
    }

    public function getHashForFilter(FilterEntity $filter): string
    {
        $arr = [];
        if ($route = $filter->getRoute()) {
            $arr[] = $route;
        }

        foreach ($filter->getRows() as $row) {
            $arr[] = $row->getField().$row->getOperator().serialize($row->getValue()).serialize($row->getMetaData());
        }

        sort($arr);

        return hash('sha1', implode('-', $arr));
    }

    /**
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function getFilterTemplateForFilter(Filter $filter): string
    {
        $template = $filter->getOption('template');

        $formBuilder = $this->formFactory->createNamedBuilder('__filterrow__', FormType::class, null, ['csrf_protection' => false]);

        $filter->preSubmitFormData($formBuilder);

        $form = $formBuilder->getForm();

        $tpl = $this->templating->render($template, [
            'data' => $form->createView(),
        ]);

        return str_replace(['__filterrow__', 'filterrow___value'], ['filter_form[rows][__name__]', 'filterrow____name____value'], $tpl);
    }

    public function getFilterResponse(Grid $grid): ?Response
    {
        $baseRoute = str_replace('.filter', '', $grid->getRoute());

        if ($grid->wasFilterSaved()) {
            $filterRoute = $baseRoute.'.filter';

            return $this->redirectToRoute($filterRoute, ['filterHash' => $grid->getFilter()->getHash()]);
        }

        if ($grid->wasFilterDeleted()) {
            return $this->redirectToRoute($baseRoute);
        }

        return null;
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
     * @throws ReflectionException
     */
    public function render(string $template, GridHelper $gridHelper, array $parameters = []): Response
    {
        $grid           = $this->getGrid($gridHelper);
        $filterResponse = $this->getFilterResponse($grid);

        return $filterResponse ?? $this->renderGrid($template, $grid, $parameters);
    }

    /**
     * @param string $template #Template
     *
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function renderGrid(string $template, Grid $grid, array $parameters = []): Response
    {
        $gridParameters = [
            'grid' => $grid,
        ];

        $content = $this->templating->render($template, array_merge($parameters, $gridParameters));

        $response = new Response();
        $response->setContent($content);

        return $response;
    }

    /**
     * @throws NonUniqueResultException
     */
    protected function getFilter(string $className, ?string $filterHash = null): FilterEntity
    {
        if ($filterHash && $filter = $this->filterRepo->findOneByHash($filterHash)) {
            return $filter;
        }

        $request = $this->requestStack->getCurrentRequest();
        if ($this->saveFilter
            && $request
            && $request->attributes->get('_route')
            && $filter = $this->filterRepo->findDefaultForRoute($request->attributes->get('_route'))
        ) {
            return $filter;
        }

        $filter = new FilterEntity($className);

        if ($request) {
            $filter->setRoute(str_replace('.filter', '', $request->attributes->get('_route')));
        }

        return $filter;
    }

    /**
     * @param string $route #Route
     */
    protected function redirectToRoute(string $route, array $parameters = [], int $status = 302): RedirectResponse
    {
        return new RedirectResponse($this->router->generate($route, $parameters), $status);
    }

    /**
     * @throws NonUniqueResultException
     * @throws ORMException
     * @throws OptimisticLockException
     */
    private function handleFilterForm(GridHelper $gridHelper): FilterFormRequest
    {
        $filterAllowedToSave = $this->saveFilter && $gridHelper->getAllowSaveFilter();

        $filter = $gridHelper->getFilter();
        $filter->setIsSaveable($filterAllowedToSave);

        $filterForm = $this->formFactory->create(FilterFormType::class, $filter, [
            'fields'             => $filter->getFields(),
            'filters'            => $gridHelper->getFilters(),
            'method'             => 'get',
            'csrf_protection'    => false,
            'allow_extra_fields' => true,
        ]);
        $ffr = new FilterFormRequest($filterForm);

        $filterForm->handleRequest($this->requestStack->getCurrentRequest());

        $qb = $gridHelper->getQueryBuilder();

        if ($filter->getHash()
            || ($filter->hasDefaultShowFilter() && !$filterForm->isSubmitted())
            || ($filterForm->isSubmitted() && $filterForm->isValid())
        ) {
            $ffr->setIsFilterApplied($filter->getHash() || ($filterForm->isSubmitted() && $filterForm->isValid()));

            $this->handleFilter($qb, $filter, $gridHelper);

            if ($filterAllowedToSave && $filterForm->get('filter_and_save')->isClicked()) {
                $this->saveFilter($filter);
                $ffr->setIsFilterSaved(true);
            }

            if ($filterAllowedToSave && $filterForm->has('delete_filter') && $filterForm->get('delete_filter')->isClicked()) {
                $this->deleteFilter($filter);
                $ffr->setIsFilterDeleted(true);
            }

            if ($filter->getHash()) {
                if ($filterForm->has('remove_default') && $filterForm->get('remove_default')->isClicked()) {
                    $this->removeFilterAsDefault($filter);
                    $ffr->setIsFilterSaved(true);
                } elseif ($filterForm->has('make_default') && $filterForm->get('make_default')->isClicked()) {
                    $this->makeFilterAsDefault($filter);
                    $ffr->setIsFilterSaved(true);
                }
            }
        }

        return $ffr;
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    private function handleColumnsForm(GridHelper $gridHelper): FilterUserSettingsFormRequest
    {
        if (!$gridHelper->getUserSettingsEnabled()) {
            throw new LogicException('User Settings are not manageable');
        }

        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            throw new LogicException('No Request Available');
        }

        $filter = $gridHelper->getFilter();

        $route      = u($request->attributes->get('_route'))->replace('.filter', '')->toString();
        $filterHash = $filter->getHash() ?? '_default';

        $filterUserSettings = $this->filterUserSettingsRepo->findOneByRouteAndUserId($route, $filterHash, $gridHelper->getCurrentUserIdentifier());
        if (!$filterUserSettings) {
            $filterUserSettings = new FilterUserSettings($route, $filterHash, $gridHelper->getCurrentUserIdentifier());
            $filterUserSettings->setVisibleColumns($gridHelper->getHideableColumns());
        }

        $filterUserSettingsForm = $this->formFactory->create(FilterUserSettingsFormType::class, $filterUserSettings, ['available_columns' => $gridHelper->getHideableColumns()]);

        $filterUserSettingsForm->handleRequest($request);
        if ($filterUserSettingsForm->isSubmitted() && $filterUserSettingsForm->isValid()) {
            $this->em->persist($filterUserSettings);
            $this->em->flush();
        }

        return new FilterUserSettingsFormRequest($filterUserSettingsForm, $filterUserSettings);
    }

    private function getPagination(GridHelper $gridHelper): PaginationInterface
    {
        $request = $this->requestStack->getMainRequest();

        $currentPage    = $request->query->getInt($gridHelper->getPageParameterName(), $gridHelper->getDefaultPage());
        $currentPerPage = $request->query->getInt($gridHelper->getPerPageParameterName(), $gridHelper->getPerPage());

        return $this->paginator->paginate(
            $gridHelper->getQueryBuilder(),
            $currentPage,
            $currentPerPage,
            [
                'wrap-queries' => $gridHelper->getWrapQueries(),
                'distinct'     => $gridHelper->getDistinctQuery(),
            ]
        );
    }
}
