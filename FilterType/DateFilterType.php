<?php


namespace Unlooped\GridBundle\FilterType;


use Carbon\Carbon;
use Doctrine\ORM\QueryBuilder;
use Exception;
use Unlooped\GridBundle\Entity\FilterRow;

class DateFilterType extends FilterType
{
    public static function getVariables(): array
    {
        return [
            'TODAY'     => '',
            'YESTERDAY' => '',
            'TOMORROW'  => '',
            'DAY_AFTER_TOMORROW'  => '',

            'START_OF_WEEK_MONDAY'      => '',
            'START_OF_WEEK_SUNDAY'      => '',
            'START_OF_LAST_WEEK_MONDAY' => '',
            'START_OF_LAST_WEEK_SUNDAY' => '',

            'END_OF_WEEK_SUNDAY'        => '',
            'END_OF_WEEK_SATURDAY'      => '',
            'END_OF_LAST_WEEK_SUNDAY'   => '',
            'END_OF_LAST_WEEK_SATURDAY' => '',

            'START_OF_MONTH'      => '',
            'END_OF_MONTH'        => '',
            'START_OF_LAST_MONTH' => '',
            'END_OF_LAST_MONTH'   => '',

            'START_OF_QUARTER'      => '',
            'END_OF_QUARTER'        => '',
            'START_OF_LAST_QUARTER' => '',
            'END_OF_LAST_QUARTER'   => '',

            'START_OF_YEAR'      => '',
            'END_OF_YEAR'        => '',
            'START_OF_LAST_YEAR' => '',
            'END_OF_LAST_YEAR'  => '',
        ];
    }

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

        $op = $this->getExpressionOperator($filterRow);
        $value = $this->getExpressionValue($filterRow);
        if ($value) {
            $value = $this->replaceVarsInValue($value);

            try {
                $date = Carbon::parse($value)->startOfDay();
            } catch (Exception $e) {
                $date = Carbon::now()->startOfDay();
            }

            if ($op === self::EXPR_EQ) {
                $endDate = $date->clone()->addDay()->startOfDay();

                $qb->andWhere($qb->expr()->gte($filterRow->getField(), ':value_start_' . $i));
                $qb->andWhere($qb->expr()->lt($filterRow->getField(), ':value_end_' . $i));

                $qb->setParameter('value_start_' . $i, $date);
                $qb->setParameter('value_end_' . $i, $endDate);
            } else {
                $qb->andWhere($qb->expr()->$op($filterRow->getField(), ':value_' . $i));
                $qb->setParameter('value_' . $i, $date);
            }

        } else {
            $qb->andWhere($qb->expr()->$op($filterRow->getField()));
        }
    }

    public function replaceVarsInValue(string $value): string
    {
        switch (strtoupper($value)) {
            case 'TODAY':
                return Carbon::now()->startOfDay()->format('Y-m-d');
            case 'YESTERDAY':
                return Carbon::now()->subDay()->startOfDay()->format('Y-m-d');
            case 'TOMORROW':
                return Carbon::now()->addDay()->startOfDay()->format('Y-m-d');
            case 'DAY_AFTER_TOMORROW':
                return Carbon::now()->addDays(2)->startOfDay()->format('Y-m-d');

            case 'START_OF_WEEK_MONDAY':
                return Carbon::now()->startOfWeek(Carbon::MONDAY)->format('Y-m-d');
            case 'START_OF_WEEK_SUNDAY':
                return Carbon::now()->startOfWeek(Carbon::SUNDAY)->format('Y-m-d');
            case 'START_OF_LAST_WEEK_MONDAY':
                return Carbon::now()->subWeek()->startOfWeek(Carbon::MONDAY)->format('Y-m-d');
            case 'START_OF_LAST_WEEK_SUNDAY':
                return Carbon::now()->subWeek()->startOfWeek(Carbon::SUNDAY)->format('Y-m-d');

            case 'END_OF_WEEK_SUNDAY':
                return Carbon::now()->endOfWeek(Carbon::SUNDAY)->format('Y-m-d');
            case 'END_OF_WEEK_SATURDAY':
                return Carbon::now()->endOfWeek(Carbon::SATURDAY)->format('Y-m-d');
            case 'END_OF_LAST_WEEK_SUNDAY':
                return Carbon::now()->subWeek()->endOfWeek(Carbon::SUNDAY)->format('Y-m-d');
            case 'END_OF_LAST_WEEK_SATURDAY':
                return Carbon::now()->subWeek()->endOfWeek(Carbon::SATURDAY)->format('Y-m-d');

            case 'START_OF_MONTH':
                return Carbon::now()->startOfMonth()->format('Y-m-d');
            case 'END_OF_MONTH':
                return Carbon::now()->endOfMonth()->format('Y-m-d');
            case 'START_OF_LAST_MONTH':
                return Carbon::now()->subMonth()->startOfMonth()->format('Y-m-d');
            case 'END_OF_LAST_MONTH':
                return Carbon::now()->subMonth()->endOfMonth()->format('Y-m-d');

            case 'START_OF_QUARTER':
                return Carbon::now()->startOfQuarter()->format('Y-m-d');
            case 'END_OF_QUARTER':
                return Carbon::now()->endOfQuarter()->format('Y-m-d');
            case 'START_OF_LAST_QUARTER':
                return Carbon::now()->subQuarter()->startOfQuarter()->format('Y-m-d');
            case 'END_OF_LAST_QUARTER':
                return Carbon::now()->subQuarter()->endOfQuarter()->format('Y-m-d');

            case 'START_OF_YEAR':
                return Carbon::now()->startOfYear()->format('Y-m-d');
            case 'END_OF_YEAR':
                return Carbon::now()->endOfYear()->format('Y-m-d');
            case 'START_OF_LAST_YEAR':
                return Carbon::now()->subYear()->startOfYear()->format('Y-m-d');
            case 'END_OF_LAST_YEAR':
                return Carbon::now()->subYear()->endOfYear()->format('Y-m-d');
        }

        return $value;
    }
}
