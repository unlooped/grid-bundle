<?php

namespace Unlooped\GridBundle\FilterType;

use Doctrine\ORM\QueryBuilder;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Unlooped\GridBundle\Entity\FilterRow;

class GlobalSearchFilterType extends AbstractFilterType
{
    /**
     * @var string[]
     */
    private array $entityJoinAliases = [];

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setRequired('fields');
        $resolver->setAllowedTypes('fields', 'string[]');
    }

    public function handleFilter(QueryBuilder $qb, FilterRow $filterRow, array $options = []): void
    {
        $searchfields = $options['fields'];
        $text         = $filterRow->getValue();

        $orX = $qb->expr()->orX();

        foreach ($searchfields as $searchfield) {
            $fields = explode('.', $searchfield);
            $field  = end($fields);

            $mappings    = [];
            $fieldsCount = \count($fields);
            for ($i = 0; $i < $fieldsCount - 1; ++$i) {
                $mappings[] = ['fieldName' => $fields[$i]];
            }
            $alias = $this->entityJoin($qb, $mappings);

            $orX->add($qb->expr()->like(\sprintf('%s.%s', $alias, $field), $qb->expr()->literal('%'.$text.'%')));
        }

        $qb->andWhere($orX);
    }

    protected static function getAvailableOperators(): array
    {
        return [
            static::EXPR_EQ => static::EXPR_EQ,
        ];
    }

    /**
     * @param array<mixed> $associationMappings
     *
     * @phpstan-param array<array{fieldName: string}> $associationMappings
     */
    private function entityJoin(QueryBuilder $qb, array $associationMappings): string
    {
        $alias = current($qb->getRootAliases());

        $newAlias = 's';

        $joinedEntities = $qb->getDQLPart('join');

        foreach ($associationMappings as $associationMapping) {
            // Do not add left join to already joined entities with custom query
            foreach ($joinedEntities as $joinExprList) {
                foreach ($joinExprList as $joinExpr) {
                    $newAliasTmp = $joinExpr->getAlias();

                    if (\sprintf('%s.%s', $alias, $associationMapping['fieldName']) === $joinExpr->getJoin()) {
                        $this->entityJoinAliases[] = $newAliasTmp;
                        $alias                     = $newAliasTmp;

                        continue 3;
                    }
                }
            }

            $newAlias .= '_'.$associationMapping['fieldName'];
            if (!\in_array($newAlias, $this->entityJoinAliases, true)) {
                $this->entityJoinAliases[] = $newAlias;
                $qb->leftJoin(\sprintf('%s.%s', $alias, $associationMapping['fieldName']), $newAlias);
            }

            $alias = $newAlias;
        }

        return $alias;
    }
}
