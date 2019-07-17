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
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Unlooped\GridBundle\Entity\Filter;
use Unlooped\GridBundle\Entity\FilterRow;
use Unlooped\GridBundle\Form\FilterType;
use Unlooped\GridBundle\Helper\GridHelper;
use Unlooped\GridBundle\Model\Grid;
use Unlooped\GridBundle\Repository\FilterRepository;
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

    public function __construct(
        RequestStack $requestStack,
        PaginatorInterface $paginator,
        FormFactoryInterface $formFactory,
        EntityManager $em,
        FlashBagInterface $flashBag,
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

        if ($saveFilter) {
            $this->filterRepo = $em->getRepository(Filter::class);
        }
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

        if ($filterHash && $filter = $this->filterRepo->findOneByHash($filterHash)) {
            return GridHelper::create($qb, $options, $filter);
        }

        $filter = new Filter();
        $filter->setEntity($className);
        $filter->addRow(new FilterRow());

        if ($request = $this->requestStack->getCurrentRequest()) {
            $filter->setRoute($request->get('_route'));
        }

        return GridHelper::create($qb, $options, $filter);
    }

    /**
     * @throws NonUniqueResultException
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function getGrid(GridHelper $gridHelper): Grid
    {
        $request = $this->requestStack->getCurrentRequest();

        $filter = $gridHelper->getFilter();
        $filter->setIsSaveable($this->saveFilter);

        $form = $this->formFactory->create(FilterType::class, $filter, ['fields' => $filter->getFields(), 'method' => 'post']);

        $form->handleRequest($request);
        $filterApplied = false;

        if ($filter->getHash() || ($form->isSubmitted() && $form->isValid())) {
            $filterApplied = true;
            $qb = $gridHelper->getQueryBuilder();

            $this->handleFilter($qb, $filter, $gridHelper);

            if ($this->saveFilter && $form->get('filter_and_save')->isClicked()) {
                $this->saveFilter($filter);
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

        $existingFilters = [];
        if ($this->saveFilter) {
            $existingFilters = $this->filterRepo->findByRoute(str_replace('.filter', '', $request->get('_route')));
        }

        return new Grid(
            $gridHelper,
            $pagination,
            $form,
            $currentPage,
            $currentPerPage,
            $this->saveFilter,
            $filterApplied,
            $request->get('_route'),
            $request->query->all(),
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

    /**
     * @throws NonUniqueResultException
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function saveFilter(Filter $filter): void
    {
        if (!$filter->getHash() && $existingFilter = $this->doesSameFilterExist($filter)) {
            $this->flashBag->add('unlooped_grid.warning', 'Filter already Exists: ' . $existingFilter->getName());
            return;
        }

        $filter->setHash($this->getHashForFilter($filter));

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
}
