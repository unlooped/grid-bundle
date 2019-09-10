<?php

namespace Unlooped\GridBundle\FilterType;

use Carbon\Carbon;
use DateTimeInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormInterface;
use Unlooped\GridBundle\Entity\FilterRow;
use Unlooped\GridBundle\Struct\DefaultFilterDataStruct;

class DateRangeFilterType extends DateFilterType
{

    protected $template = '@UnloopedGrid/filter_types/date_range.html.twig';

    public static function getAvailableOperators(): array
    {
        return [
            self::EXPR_IN_RANGE => self::EXPR_IN_RANGE,
        ];
    }

    public static function createDefaultDataForRangeVariables(string $fromDate, string $toDate): DefaultFilterDataStruct
    {
        $dfds = new DefaultFilterDataStruct();
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
        $dfds = new DefaultFilterDataStruct();
        $dfds->operator = self::EXPR_IN_RANGE;
        $dfds->metaData = [
            'value_type' => self::VALUE_CHOICE_DATE,
            'dateValue_from' => Carbon::instance($fromDate)->toFormattedDateString(),
            'dateValue_to' => Carbon::instance($toDate)->toFormattedDateString(),
        ];

        return $dfds;
    }

    public function handleFilter(QueryBuilder $qb, FilterRow $filterRow): void
    {
        $i = self::$cnt++;

        $field = $this->getFieldInfo($qb, $filterRow);
        $metaData = $filterRow->getMetaData();

        if ($metaData['value_type'] === self::VALUE_CHOICE_VARIABLES) {
            $startValue = $metaData['variable_from'];
            $endValue = $metaData['variable_to'];
        } else {
            $startValue = $metaData['dateValue_from'];
            $endValue = $metaData['dateValue_to'];
        }

        if ($startValue) {
            if (is_string($startValue)) {
                $startValue = $this->replaceVarsInValue($startValue);
            }

            $startDate = Carbon::parse($startValue, $this->options['view_timezone'])->startOfDay();

            $qb->andWhere($qb->expr()->gte($field, ':value_start_' . $i));
            $qb->setParameter('value_start_' . $i, $startDate->timezone($this->options['target_timezone']));
        }

        if ($endValue) {
            if (is_string($endValue)) {
                $endValue = $this->replaceVarsInValue($endValue);
            }

            $endDate = Carbon::parse($endValue, $this->options['view_timezone'])->addDay()->startOfDay();
            $qb->andWhere($qb->expr()->lt($field, ':value_end_' . $i));
            $qb->setParameter('value_end_' . $i, $endDate->timezone($this->options['target_timezone']));
        }
    }

    /**
     * @param FormBuilderInterface|FormInterface $builder
     * @param FilterRow|array $data
     * @param array $options
     */
    public function buildForm($builder, array $options = [], $data = null): void
    {
        $hideVariables = true;
        $hideDate = false;
        if ($data
            && is_a($data, FilterRow::class, true)
            && $data->getMetaData()
            && array_key_exists('value_type', $data->getMetaData())
            && $data->getMetaData()['value_type'] === self::VALUE_CHOICE_VARIABLES)
        {
            $hideDate = true;
            $hideVariables = false;
        }

        $builder
            ->add('_valueChoices', ChoiceType::class, [
                'translation_domain' => 'unlooped_grid',
                'mapped' => false,
                'choices' => self::getValueChoices(),
                'attr' => [
                    'class' => 'custom-select'
                ],
            ])
            ->add('_variables_from', ChoiceType::class, [
                'translation_domain' => 'unlooped_grid',
                'mapped'  => false,
                'required' => false,
                'choices' => self::getVariables(),
                'attr'    => [
                    'class' => 'custom-select' . ($hideVariables ? ' d-none' : ''),
                ],
            ])
            ->add('_dateValue_from', DateType::class, [
                'mapped' => false,
                'required' => false,
                'widget' => 'single_text',
                'attr'    => [
                    'class' => $hideDate ? ' d-none' : '',
                ],
            ])
            ->add('_variables_to', ChoiceType::class, [
                'translation_domain' => 'unlooped_grid',
                'mapped'  => false,
                'required' => false,
                'choices' => self::getVariables(),
                'attr'    => [
                    'class' => 'custom-select' . ($hideVariables ? ' d-none' : ''),
                ],
            ])
            ->add('_dateValue_to', DateType::class, [
                'mapped' => false,
                'widget' => 'single_text',
                'required' => false,
                'attr'    => [
                    'class' => $hideDate ? ' d-none' : '',
                ],
            ])
            ->remove('value')
        ;
    }

    /**
     * @param FormBuilderInterface|FormInterface $builder
     * @param array $options
     * @param FilterRow $data
     * @param FormEvent|null $event
     */
    public function postSetFormData($builder, array $options = [], $data = null, FormEvent $event = null): void
    {
        $this->buildForm($builder, [], $data);

        $valueType = $data->getMetaData()['value_type'];

        $builder->get('_valueChoices')->setData($valueType);

        if ($valueType === self::VALUE_CHOICE_VARIABLES) {
            $builder->get('_variables_from')->setData($data->getMetaData()['variable_from']);
            $builder->get('_variables_to')->setData($data->getMetaData()['variable_to']);
        } else if ($valueType === self::VALUE_CHOICE_DATE) {
            $builder->get('_dateValue_from')->setData(Carbon::parse($data->getMetaData()['dateValue_from']));
            $builder->get('_dateValue_to')->setData(Carbon::parse($data->getMetaData()['dateValue_to']));
        }
    }

    /**
     * @param FormBuilderInterface|FormInterface $builder
     * @param array $options
     * @param FilterRow $data
     * @param FormEvent|null $event
     */
    public function postFormSubmit($builder, array $options = [], $data = null, FormEvent $event = null): void
    {
        $valueType = $builder->get('_valueChoices')->getData();
        if ($valueType === self::VALUE_CHOICE_DATE) {
            $dateFrom = $builder->get('_dateValue_from')->getData() !== null ? Carbon::parse($builder->get('_dateValue_from')->getData())->toFormattedDateString() : null;
            $dateTo = $builder->get('_dateValue_to')->getData() !== null ? Carbon::parse($builder->get('_dateValue_to')->getData())->toFormattedDateString() : null;

            $data->setMetaData([
                'value_type'     => $valueType,
                'dateValue_from' => $dateFrom,
                'dateValue_to'   => $dateTo,
            ]);
        } else if ($valueType === self::VALUE_CHOICE_VARIABLES) {
            $data->setMetaData([
                'value_type'    => $valueType,
                'variable_from' => $builder->get('_variables_from')->getData(),
                'variable_to'   => $builder->get('_variables_to')->getData(),
            ]);
        }
    }

}
