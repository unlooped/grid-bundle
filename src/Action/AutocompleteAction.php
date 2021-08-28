<?php

namespace Unlooped\GridBundle\Action;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Unlooped\GridBundle\Entity\FilterRow;
use Unlooped\GridBundle\Filter\Filter;
use Unlooped\GridBundle\FilterType\AutocompleteFilterType;
use Unlooped\GridBundle\FilterType\AutocompleteTextFilterType;
use Unlooped\GridBundle\Grid\Grid;
use Unlooped\GridBundle\Service\GridService;

class AutocompleteAction
{
    private ManagerRegistry $registry;

    private GridService $gridService;

    private ContainerInterface $container;

    public function __construct(
        ManagerRegistry $registry,
        GridService $gridService,
        ContainerInterface $container
    ) {
        $this->registry    = $registry;
        $this->gridService = $gridService;
        $this->container   = $container;
    }

    public function __invoke(
        Request $request
    ): JsonResponse {
        $grid  = $request->get('grid');
        $field = $request->get('field');

        $grid = $this->getGrid($grid);

        if (null === $grid || null === $field) {
            return new JsonResponse([]);
        }

        $gh = $this->gridService->getGridHelper($grid->getModel());
        $grid->configure($gh);

        $filter = $gh->getFilterTypeForField($field);

        if (!$this->isSupportedFilter($filter)) {
            return new JsonResponse([]);
        }

        $result = $this->getResult($filter, $request->get('q', ''), (int) $request->get('page', 1));

        return new JsonResponse($result);
    }

    private function getGrid(string $name): ?Grid
    {
        if (!$this->container->has($name)) {
            return null;
        }

        $grid = $this->container->get($name);

        if (!$grid instanceof Grid) {
            return null;
        }

        return $grid;
    }

    private function getResult(Filter $filter, string $term, int $page): array
    {
        $entity           = $filter->getOption('entity');
        $textProperty     = $filter->getOption('text_property');
        $entityPrimaryKey = $filter->getOption('entity_primary_key', $textProperty);
        $queryBuilder     = $filter->getOption('query_builder');
        $minLength        = (int) $filter->getOption('minimum_input_length');

        if (\strlen($term) < $minLength) {
            return [];
        }

        $repository = $this->getRepository($entity);

        $count    = $this
            ->createQueryBuilder($repository, $textProperty, $term, $queryBuilder)
            ->select('COUNT(e)')
            ->getQuery()
            ->getSingleScalarResult()
        ;

        $maxResults = 10;
        $offset     = ($page - 1) * $maxResults;

        $paginationResults = $this
            ->createQueryBuilder($repository, $textProperty, $term, $queryBuilder)
            ->setMaxResults($maxResults)
            ->setFirstResult($offset)
            ->addGroupBy(sprintf('e.%s', $textProperty))
            ->getQuery()
            ->getResult()
        ;

        $accessor = PropertyAccess::createPropertyAccessor();

        $result = [
            'results' => null,
            'more'    => $count > ($offset + $maxResults),
        ];
        $result['results'] = array_map(
            static function ($item) use ($accessor, $entityPrimaryKey, $textProperty): array {
                return [
                    'id'   => $accessor->getValue($item, $entityPrimaryKey),
                    'text' => $accessor->getValue($item, $textProperty),
                ];
            },
            $paginationResults
        );

        return $result;
    }

    private function getRepository(string $class): EntityRepository
    {
        $manager = $this->registry->getManagerForClass($class);

        \assert($manager instanceof EntityManager);

        $repository = $manager->getRepository($class);

        \assert($repository instanceof EntityRepository);

        return $repository;
    }

    private function createQueryBuilder(EntityRepository $repository, string $field, string $term, ?callable $queryBuiler): QueryBuilder
    {
        $qb = $repository->createQueryBuilder('e')
            ->where(sprintf('e.%s LIKE :term', $field))
            ->setParameter('term', '%'.$term.'%')
        ;

        if (null !== $queryBuiler) {
            $filterRow = new FilterRow();
            $filterRow->setField($field);
            $filterRow->setValue($term);

            return $queryBuiler($qb, $filterRow);
        }

        return $qb;
    }

    private function isSupportedFilter(Filter $filter): bool
    {
        $filterType = $filter->getType();

        return $filterType instanceof AutocompleteFilterType || $filterType instanceof AutocompleteTextFilterType;
    }

    private function isSupportedFilter(Filter $filter): bool
    {
        $filterType = $filter->getType();

        return $filterType instanceof AutocompleteFilterType || $filterType instanceof AutocompleteTextFilterType;
    }
}
