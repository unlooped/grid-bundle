<?php

namespace Unlooped\GridBundle\Service;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Unlooped\GridBundle\Filter\Filter;
use Unlooped\GridBundle\FilterType\AutocompleteFilterType;
use Unlooped\GridBundle\FilterType\AutocompleteTextFilterType;
use Unlooped\GridBundle\Helper\GridHelper;

class AutocompleteService
{
    private ManagerRegistry $registry;

    public function __construct(
        ManagerRegistry $registry
    ) {
        $this->registry = $registry;
    }

    public function getAutocompleteResults(GridHelper $gridHelper, Request $request): Response
    {
        $filter = $gridHelper->getFilterTypeForField($request->get('field'));

        if (!$this->isSupportedFilter($filter)) {
            return new JsonResponse([]);
        }

        $q         = $request->get('q', '');
        $page      = (int) $request->get('page', 1);
        $pageLimit = (int) $request->get('page_limit', 10);

        $result = $this->getResult($filter, $q, $page, $pageLimit);

        return new JsonResponse($result);
    }

    private function getResult(
        Filter $filter,
        string $term,
        int $page,
        int $pageLimit = 10
    ): array {
        $entity           = $filter->getOption('entity');
        $property         = $filter->getOption('property');
        $gridField        = $filter->getOption('grid_field');
        $entityPrimaryKey = $filter->getOption('entity_primary_key', $gridField);
        $minLength        = (int) $filter->getOption('minimum_input_length');
        $searchProperty   = $property ?? $gridField;
        $filterOptions    = $filter->getOptions();

        if (\strlen($term) < $minLength) {
            return [];
        }

        $repository = $this->getRepository($entity);

        $count = $this
            ->createQueryBuilder($repository, $filterOptions, $term)
            ->select('COUNT(e)')
            ->getQuery()
            ->getSingleScalarResult()
        ;

        $maxResults = $pageLimit;
        $offset     = ($page - 1) * $maxResults;

        $paginationResults = $this
            ->createQueryBuilder($repository, $filterOptions, $term)
            ->setMaxResults($maxResults)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult()
        ;

        $accessor = PropertyAccess::createPropertyAccessor();

        $result            = [
            'results' => null,
            'more'    => $count > ($offset + $maxResults),
        ];
        $result['results'] = array_map(
            static function ($item) use ($accessor, $entityPrimaryKey, $filterOptions): array {
                if ($filterOptions['text_property']) {
                    $text = $accessor->getValue($item, $filterOptions['text_property']);
                } else {
                    $text = $item->__toString();
                }

                return [
                    'id'   => $accessor->getValue($item, $entityPrimaryKey),
                    'text' => $text,
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

    private function createQueryBuilder(
        EntityRepository $repository,
        array $options,
        string $term
    ): QueryBuilder {
        $field = $options['property'] ?? $options['grid_field'];

        $qb = $repository->createQueryBuilder('e')
            ->setParameter('term', '%'.$term.'%')
            ->setParameter('rawTerm', $term)
        ;

        if (\is_array($field)) {
            foreach ($field as $property) {
                $qb->orWhere(sprintf('e.%s LIKE :term', $field))
                    ->addOrderBy(sprintf('LOCATE(:rawTerm, e.%s)', $property), 'ASC')
                    ->addOrderBy(sprintf('e.%s', $property), 'ASC')
                ;
            }
        } else {
            $qb
                ->where(sprintf('e.%s LIKE :term', $field))
                ->addOrderBy(sprintf('LOCATE(:rawTerm, e.%s)', $field), 'ASC')
                ->addOrderBy(sprintf('e.%s', $field), 'ASC')
            ;
        }

        if (null !== $options['filter_callback']) {
            $options['filter_callback']($qb, $term);
        }

        return $qb;
    }

    private function isSupportedFilter(Filter $filter): bool
    {
        $filterType = $filter->getType();

        return $filterType instanceof AutocompleteFilterType || $filterType instanceof AutocompleteTextFilterType;
    }
}
