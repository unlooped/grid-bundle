<?php

namespace Unlooped\GridBundle\FilterType;

use Carbon\Carbon;
use DateTimeInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormEvent;
use Unlooped\GridBundle\Entity\FilterRow;
use Unlooped\GridBundle\Struct\DefaultFilterDataStruct;

class DateRangeFilterType extends DateFilterType
{
    protected $template = '@UnloopedGrid/filter_types/date_range.html.twig';

    public static function createDefaultDataForRangeVariables(string $fromDate, string $toDate): DefaultFilterDataStruct
    {
        $dfds           = new DefaultFilterDataStruct();
        $dfds->operator = self::EXPR_IN_RANGE;
        $dfds->metaData = [
            'value_type'    => self::VALUE_CHOICE_VARIABLES,
            'variable_from' => $fromDate,
            'variable_to'   => $toDate,
        ];

        return $dfds;
    }

    public static function createDefaultDataForDateRange(DateTimeInterface $fromDate, DateTimeInterface $toDate): DefaultFilterDataStruct
    {
        $dfds           = new DefaultFilterDataStruct();
        $dfds->operator = self::EXPR_IN_RANGE;
        $dfds->metaData = [
            'value_type'     => self::VALUE_CHOICE_DATE,
            'dateValue_from' => Carbon::instance($fromDate)->toFormattedDateString(),
            'dateValue_to'   => Carbon::instance($toDate)->toFormattedDateString(),
        ];

        return $dfds;
    }

    public function handleFilter(QueryBuilder $qb, FilterRow $filterRow, array $options = []): void
    {
        $suffix = uniqid('', false);

        $field    = $this->getFieldInfo($qb, $filterRow);
        $metaData = $filterRow->getMetaData();

        if (!\array_key_exists('value_type', $metaData)) {
            $startValue = null;
            $endValue   = null;
        } elseif (self::VALUE_CHOICE_VARIABLES === $metaData['value_type']) {
            $startValue = $metaData['variable_from'];
            $endValue   = $metaData['variable_to'];
        } else {
            $startValue = $metaData['dateValue_from'];
            $endValue   = $metaData['dateValue_to'];
        }

        if ($startValue) {
            if (\is_string($startValue)) {
                $startValue = $this->replaceVarsInValue($startValue, $options);
            }

            $startDate = Carbon::parse($startValue, $options['view_timezone'])->startOfDay();

            $qb->andWhere($qb->expr()->gte($field, ':value_start_'.$suffix));
            $qb->setParameter('value_start_'.$suffix, $startDate->timezone($options['target_timezone']));
        }

        if ($endValue) {
            if (\is_string($endValue)) {
                $endValue = $this->replaceVarsInValue($endValue, $options);
            }

            $endDate = Carbon::parse($endValue, $options['view_timezone'])->addDay()->startOfDay();
            $qb->andWhere($qb->expr()->lt($field, ':value_end_'.$suffix));
            $qb->setParameter('value_end_'.$suffix, $endDate->timezone($options['target_timezone']));
        }
    }

    public function buildForm($builder, array $options = [], $data = null): void
    {
        $hideVariables = true;
        $hideDate      = false;

        if (null !== $data
            && is_a($data, FilterRow::class, true)
            && $data->getMetaData()
            && \array_key_exists('value_type', $data->getMetaData())
            && self::VALUE_CHOICE_VARIABLES === $data->getMetaData()['value_type']) {
            $hideDate      = true;
            $hideVariables = false;
        }

        $builder
            ->remove('value')
            ->add('_valueChoices', ChoiceType::class, [
                'translation_domain' => 'unlooped_grid',
                'mapped'             => false,
                'choices'            => self::getValueChoices(),
                'attr'               => [
                    'class' => 'custom-select',
                ],
            ])
            ->add('_variables_from', ChoiceType::class, [
                'translation_domain' => 'unlooped_grid',
                'mapped'             => false,
                'required'           => false,
                'choices'            => self::getVariables(),
                'attr'               => [
                    'class' => 'custom-select'.($hideVariables ? ' d-none' : ''),
                ],
            ])
            ->add('_dateValue_from', DateType::class, [
                'mapped'   => false,
                'required' => false,
                'widget'   => 'single_text',
                'attr'     => [
                    'class' => $hideDate ? ' d-none' : '',
                ],
            ])
            ->add('_variables_to', ChoiceType::class, [
                'translation_domain' => 'unlooped_grid',
                'mapped'             => false,
                'required'           => false,
                'choices'            => self::getVariables(),
                'attr'               => [
                    'class' => 'custom-select'.($hideVariables ? ' d-none' : ''),
                ],
            ])
            ->add('_dateValue_to', DateType::class, [
                'mapped'   => false,
                'widget'   => 'single_text',
                'required' => false,
                'attr'     => [
                    'class' => $hideDate ? ' d-none' : '',
                ],
            ])
        ;
    }

    public function getFormFieldNames(): array
    {
        return [
            '_valueChoices',
            '_variables_from',
            '_dateValue_from',
            '_variables_to',
            '_dateValue_to',
        ];
    }

    public function postSetFormData($builder, array $options = [], $data = null, FormEvent $event = null): void
    {
        $this->buildForm($builder, [], $data);

        $metaData = $data->getMetaData();
        if (!\array_key_exists('value_type', $metaData)) {
            return;
        }

        $valueType = $metaData['value_type'];
        $builder->get('_valueChoices')->setData($valueType);

        if (self::VALUE_CHOICE_VARIABLES === $valueType) {
            $builder->get('_variables_from')->setData($metaData['variable_from']);
            $builder->get('_variables_to')->setData($metaData['variable_to']);
        } elseif (self::VALUE_CHOICE_DATE === $valueType) {
            $builder->get('_dateValue_from')->setData(Carbon::parse($metaData['dateValue_from']));
            $builder->get('_dateValue_to')->setData(Carbon::parse($metaData['dateValue_to']));
        }
    }

    public function postFormSubmit($builder, array $options = [], $data = null, FormEvent $event = null): void
    {
        $valueType = $builder->get('_valueChoices')->getData();
        if (self::VALUE_CHOICE_DATE === $valueType) {
            $dateFrom = null !== $builder->get('_dateValue_from')->getData() ? Carbon::parse($builder->get('_dateValue_from')->getData())->toFormattedDateString() : null;
            $dateTo   = null !== $builder->get('_dateValue_to')->getData() ? Carbon::parse($builder->get('_dateValue_to')->getData())->toFormattedDateString() : null;

            $data->setMetaData([
                'value_type'     => $valueType,
                'dateValue_from' => $dateFrom,
                'dateValue_to'   => $dateTo,
            ]);
        } elseif (self::VALUE_CHOICE_VARIABLES === $valueType) {
            $data->setMetaData([
                'value_type'    => $valueType,
                'variable_from' => $builder->get('_variables_from')->getData(),
                'variable_to'   => $builder->get('_variables_to')->getData(),
            ]);
        }
    }

    protected static function getAvailableOperators(): array
    {
        return [
            self::EXPR_IN_RANGE => self::EXPR_IN_RANGE,
        ];
    }
}
