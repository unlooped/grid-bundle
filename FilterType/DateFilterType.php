<?php


namespace Unlooped\GridBundle\FilterType;


use App\Exception\DateFilterValueChoiceDoesNotExistException;
use App\Exception\OperatorDoesNotExistException;
use Carbon\Carbon;
use DateTimeInterface;
use Doctrine\ORM\QueryBuilder;
use Exception;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Unlooped\GridBundle\Entity\FilterRow;
use Unlooped\GridBundle\Struct\DefaultFilterDataStruct;
use Unlooped\Helper\ConstantHelper;

class DateFilterType extends FilterType
{

    public const VALUE_CHOICE_DATE = 'date';
    public const VALUE_CHOICE_VARIABLES = 'variables';

    protected $template = '@UnloopedGrid/filter_types/date.html.twig';

    public static function getVariables(): array
    {
        return [
            'DAYS' => [
                'TODAY'              => 'TODAY',
                'YESTERDAY'          => 'YESTERDAY',
                'TOMORROW'           => 'TOMORROW',
                'DAY_AFTER_TOMORROW' => 'DAY_AFTER_TOMORROW',
            ],
            'WEEKS' => [
                'ONE_WEEK_AGO'    => 'ONE_WEEK_AGO',
                'TWO_WEEKS_AGO'   => 'TWO_WEEKS_AGO',
                'THREE_WEEKS_AGO' => 'THREE_WEEKS_AGO',
                'FOUR_WEEKS_AGO'  => 'FOUR_WEEKS_AGO',
            ],
            'MONDAYS_WEEKS' => [
                'START_OF_WEEK_MONDAY'      => 'START_OF_WEEK_MONDAY',
                'START_OF_LAST_WEEK_MONDAY' => 'START_OF_LAST_WEEK_MONDAY',
                'START_OF_NEXT_WEEK_MONDAY' => 'START_OF_NEXT_WEEK_MONDAY',
                'END_OF_WEEK_SUNDAY'        => 'END_OF_WEEK_SUNDAY',
                'END_OF_LAST_WEEK_SUNDAY'   => 'END_OF_LAST_WEEK_SUNDAY',
                'END_OF_NEXT_WEEK_SUNDAY'   => 'END_OF_LAST_WEEK_SUNDAY',
            ],
            'SUNDAY_WEEKS' => [
                'START_OF_WEEK_SUNDAY'      => 'START_OF_WEEK_SUNDAY',
                'START_OF_LAST_WEEK_SUNDAY' => 'START_OF_LAST_WEEK_SUNDAY',
                'START_OF_NEXT_WEEK_SUNDAY' => 'START_OF_NEXT_WEEK_SUNDAY',
                'END_OF_WEEK_SATURDAY'      => 'END_OF_WEEK_SATURDAY',
                'END_OF_LAST_WEEK_SATURDAY' => 'END_OF_LAST_WEEK_SATURDAY',
                'END_OF_NEXT_WEEK_SATURDAY' => 'END_OF_LAST_WEEK_SATURDAY',
            ],
            'MONTHS' => [
                'START_OF_MONTH'      => 'START_OF_MONTH',
                'END_OF_MONTH'        => 'END_OF_MONTH',
                'START_OF_LAST_MONTH' => 'START_OF_LAST_MONTH',
                'END_OF_LAST_MONTH'   => 'END_OF_LAST_MONTH',
                'START_OF_NEXT_MONTH' => 'START_OF_NEXT_MONTH',
                'END_OF_NEXT_MONTH'   => 'END_OF_NEXT_MONTH',
            ],
            'QUARTERS' => [
                'START_OF_QUARTER'      => 'START_OF_QUARTER',
                'END_OF_QUARTER'        => 'END_OF_QUARTER',
                'START_OF_LAST_QUARTER' => 'START_OF_LAST_QUARTER',
                'END_OF_LAST_QUARTER'   => 'END_OF_LAST_QUARTER',
                'START_OF_NEXT_QUARTER' => 'START_OF_NEXT_QUARTER',
                'END_OF_NEXT_QUARTER'   => 'END_OF_NEXT_QUARTER',
            ],
            'YEARS' => [
                'START_OF_YEAR'      => 'START_OF_YEAR',
                'END_OF_YEAR'        => 'END_OF_YEAR',
                'START_OF_LAST_YEAR' => 'START_OF_LAST_YEAR',
                'END_OF_LAST_YEAR'   => 'END_OF_LAST_YEAR',
                'START_OF_NEXT_YEAR' => 'START_OF_NEXT_YEAR',
                'END_OF_NEXT_YEAR'   => 'END_OF_NEXT_YEAR',
            ],
        ];
    }

    public static function getValueChoices(): array
    {
        return ConstantHelper::getList('VALUE_CHOICE');
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

    /**
     * @throws DateFilterValueChoiceDoesNotExistException
     * @throws OperatorDoesNotExistException
     */
    public static function createDefaultData(
        string $operator,
        $value = null,
        string $valueChoice = self::VALUE_CHOICE_DATE
    ): DefaultFilterDataStruct
    {
        $dfds = parent::createDefaultData($operator, $value);

        if (!in_array($valueChoice, self::getValueChoices(), true)) {
            throw new DateFilterValueChoiceDoesNotExistException($valueChoice, self::class);
        }

        $metaData = [
            'value_type' => $valueChoice,
        ];

        if ($valueChoice === self::VALUE_CHOICE_VARIABLES) {
            $metaData['variable'] = $value;
        }

        $dfds->metaData = $metaData;

        return $dfds;
    }

    /**
     * @throws DateFilterValueChoiceDoesNotExistException
     * @throws OperatorDoesNotExistException
     */
    public static function createDefaultDataForDate(string $operator, DateTimeInterface $dateTime): DefaultFilterDataStruct
    {
        return self::createDefaultData($operator, Carbon::instance($dateTime)->toFormattedDateString());
    }


    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);
        $resolver->setDefaults([
            'widget'        => 'date',
            'value_choices' => self::getValueChoices(),
            'choices'       => self::getVariables(),
            'view_timezone' => date_default_timezone_get(),
            'target_timezone' => 'UTC',
        ]);

        $resolver->setAllowedValues('widget', ['text', 'date', 'datetime', 'datepicker', 'datetimepicker']);
        $resolver->setAllowedTypes('value_choices', ['array']);
        $resolver->setAllowedTypes('choices', ['array']);
        $resolver->setAllowedTypes('view_timezone', ['null', 'string']);
        $resolver->setAllowedTypes('target_timezone', ['string']);
    }

    public function handleFilter(QueryBuilder $qb, FilterRow $filterRow): void
    {
        $i = self::$cnt++;

        $op = $this->getExpressionOperator($filterRow);
        $value = $this->getExpressionValue($filterRow);
        $field = $this->getFieldInfo($qb, $filterRow);

        if ($value) {
            if (is_string($value)) {
                $value = $this->replaceVarsInValue($value);
            }

            try {
                $date = Carbon::parse($value, $this->options['view_timezone'])->startOfDay();
            } catch (Exception $e) {
                $date = Carbon::now($this->options['view_timezone'])->startOfDay();
            }

            if ($op === self::EXPR_EQ) {
                $endDate = $date->clone()->addDay()->startOfDay();

                $qb->andWhere($qb->expr()->gte($field, ':value_start_' . $i));
                $qb->andWhere($qb->expr()->lt($field, ':value_end_' . $i));

                $qb->setParameter('value_start_' . $i, $date->timezone($this->options['target_timezone']));
                $qb->setParameter('value_end_' . $i, $endDate->timezone($this->options['target_timezone']));
            } else {
                $qb->andWhere($qb->expr()->$op($field, ':value_' . $i));
                $qb->setParameter('value_' . $i, $date->timezone($this->options['target_timezone']));
            }

        } elseif (!$this->hasExpressionValue($filterRow)) {
            $qb->andWhere($qb->expr()->$op($field));
        }
    }

    public function replaceVarsInValue(string $value): string
    {
        $now = Carbon::now($this->options['view_timezone']);
        $now->settings([
            'monthOverflow' => false,
            'yearOverflow' => false,
        ]);

        switch (strtoupper($value)) {
            case 'TODAY':
                return $now->startOfDay()->toFormattedDateString();
            case 'YESTERDAY':
                return $now->subDay()->startOfDay()->toFormattedDateString();
            case 'TOMORROW':
                return $now->addDay()->startOfDay()->toFormattedDateString();
            case 'DAY_AFTER_TOMORROW':
                return $now->addDays(2)->startOfDay()->toFormattedDateString();

            case 'ONE_WEEK_AGO':
                return $now->subWeek()->startOfDay()->toFormattedDateString();
            case 'TWO_WEEKS_AGO':
                return $now->subWeeks(2)->startOfDay()->toFormattedDateString();
            case 'THREE_WEEKS_AGO':
                return $now->subWeeks(3)->startOfDay()->toFormattedDateString();
            case 'FOUR_WEEKS_AGO':
                return $now->subWeeks(4)->startOfDay()->toFormattedDateString();

            case 'START_OF_WEEK_MONDAY':
                return $now->startOfWeek(Carbon::MONDAY)->toFormattedDateString();
            case 'START_OF_WEEK_SUNDAY':
                return $now->startOfWeek(Carbon::SUNDAY)->toFormattedDateString();
            case 'START_OF_LAST_WEEK_MONDAY':
                return $now->subWeek()->startOfWeek(Carbon::MONDAY)->toFormattedDateString();
            case 'START_OF_LAST_WEEK_SUNDAY':
                return $now->subWeek()->startOfWeek(Carbon::SUNDAY)->toFormattedDateString();
            case 'START_OF_NEXT_WEEK_MONDAY':
                return $now->addWeek()->startOfWeek(Carbon::MONDAY)->toFormattedDateString();
            case 'START_OF_NEXT_WEEK_SUNDAY':
                return $now->addWeek()->startOfWeek(Carbon::SUNDAY)->toFormattedDateString();

            case 'END_OF_WEEK_SUNDAY':
                return $now->endOfWeek(Carbon::SUNDAY)->toFormattedDateString();
            case 'END_OF_WEEK_SATURDAY':
                return $now->endOfWeek(Carbon::SATURDAY)->toFormattedDateString();
            case 'END_OF_LAST_WEEK_SUNDAY':
                return $now->subWeek()->endOfWeek(Carbon::SUNDAY)->toFormattedDateString();
            case 'END_OF_LAST_WEEK_SATURDAY':
                return $now->subWeek()->endOfWeek(Carbon::SATURDAY)->toFormattedDateString();
            case 'END_OF_NEXT_WEEK_SUNDAY':
                return $now->addWeek()->endOfWeek(Carbon::SUNDAY)->toFormattedDateString();
            case 'END_OF_NEXT_WEEK_SATURDAY':
                return $now->addWeek()->endOfWeek(Carbon::SATURDAY)->toFormattedDateString();

            case 'START_OF_MONTH':
                return $now->startOfMonth()->toFormattedDateString();
            case 'END_OF_MONTH':
                return $now->endOfMonth()->toFormattedDateString();
            case 'START_OF_LAST_MONTH':
                return $now->subMonth()->startOfMonth()->toFormattedDateString();
            case 'END_OF_LAST_MONTH':
                return $now->subMonth()->endOfMonth()->toFormattedDateString();
            case 'START_OF_NEXT_MONTH':
                return $now->addMonth()->startOfMonth()->toFormattedDateString();
            case 'END_OF_NEXT_MONTH':
                return $now->addMonth()->endOfMonth()->toFormattedDateString();

            case 'START_OF_QUARTER':
                return $now->startOfQuarter()->toFormattedDateString();
            case 'END_OF_QUARTER':
                return $now->endOfQuarter()->toFormattedDateString();
            case 'START_OF_LAST_QUARTER':
                return $now->subQuarter()->startOfQuarter()->toFormattedDateString();
            case 'END_OF_LAST_QUARTER':
                return $now->subQuarter()->endOfQuarter()->toFormattedDateString();
            case 'START_OF_NEXT_QUARTER':
                return $now->addQuarter()->startOfQuarter()->toFormattedDateString();
            case 'END_OF_NEXT_QUARTER':
                return $now->addQuarter()->endOfQuarter()->toFormattedDateString();

            case 'START_OF_YEAR':
                return $now->startOfYear()->toFormattedDateString();
            case 'END_OF_YEAR':
                return $now->endOfYear()->toFormattedDateString();
            case 'START_OF_LAST_YEAR':
                return $now->subYear()->startOfYear()->toFormattedDateString();
            case 'END_OF_LAST_YEAR':
                return $now->subYear()->endOfYear()->toFormattedDateString();
            case 'START_OF_NEXT_YEAR':
                return $now->addYear()->startOfYear()->toFormattedDateString();
            case 'END_OF_NEXT_YEAR':
                return $now->addYear()->endOfYear()->toFormattedDateString();
        }

        return $value;
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
            ->add('_variables', ChoiceType::class, [
                'translation_domain' => 'unlooped_grid',
                'mapped'  => false,
                'choices' => self::getVariables(),
                'attr'    => [
                    'class' => 'custom-select' . ($hideVariables ? ' d-none' : ''),
                ],
            ])
            ->add('_dateValue', DateType::class, [
                'mapped' => false,
                'widget' => 'single_text',
                'attr'    => [
                    'class' => $hideDate ? ' d-none' : '',
                ],
            ])
            ->remove('value')
        ;
    }

    public function getFormFieldNames(): array
    {
        return [
            '_valueChoices',
            '_variables',
            '_dateValue',
        ];
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

        $valueType = array_key_exists('value_type', $data->getMetaData()) ? $data->getMetaData()['value_type'] : self::VALUE_CHOICE_DATE;

        $builder->get('_valueChoices')->setData($valueType);

        if ($valueType === self::VALUE_CHOICE_VARIABLES) {
            $builder->get('_variables')->setData($data->getValue());
        } else if ($valueType === self::VALUE_CHOICE_DATE) {
            if ($data->getValue()) {
                $builder->get('_dateValue')->setData(Carbon::parse($data->getValue()));
            }
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
            $date = $builder->get('_dateValue')->getData() !== null ? Carbon::parse($builder->get('_dateValue')->getData())->toFormattedDateString() : null;
            $data->setValue($date);
            $data->setMetaData(['value_type' => $valueType]);
        } else if ($valueType === self::VALUE_CHOICE_VARIABLES) {
            $data->setValue($builder->get('_variables')->getData());
            $data->setMetaData([
                'value_type' => $valueType,
                'variable' => $data->getValue(),
            ]);
        }
    }
}
