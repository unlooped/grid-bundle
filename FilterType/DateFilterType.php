<?php


namespace Unlooped\GridBundle\FilterType;


use Doctrine\ORM\QueryBuilder;
use Unlooped\GridBundle\Entity\FilterRow;

class DateFilterType extends FilterType
{

    public static function getAvailableOperators(): array
    {
        return [
            self::EXPR_EQ           => self::EXPR_EQ,
            self::EXPR_LT           => self::EXPR_LT,
            self::EXPR_LTE          => self::EXPR_LTE,
            self::EXPR_GT           => self::EXPR_GT,
            self::EXPR_GTE          => self::EXPR_GTE,
            self::EXPR_IS_EMPTY     => self::EXPR_IS_EMPTY,
            self::EXPR_IS_NOT_EMPTY => self::EXPR_IS_NOT_EMPTY,
        ];
    }

    public function handleFilter(QueryBuilder $qb, FilterRow $filterRow): void
    {
        $i = self::$cnt++;

        $op = $this->getExpressionOperator();
        $value = $this->getExpressionValue();
        if ($value) {
            $qb->andWhere($qb->expr()->$op($filterRow->getField(), ':value_' . $i));
            $qb->setParameter('value_' . $i, $value);
        } else {
            $qb->andWhere($qb->expr()->$op($filterRow->getField()));
        }
    }
}
