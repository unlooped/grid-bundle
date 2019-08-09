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
            'TODAY'              => 'TODAY',
            'YESTERDAY'          => 'YESTERDAY',
            'TOMORROW'           => 'TOMORROW',
            'DAY_AFTER_TOMORROW' => 'DAY_AFTER_TOMORROW',

            'ONE_WEEK_AGO'    => 'ONE_WEEK_AGO',
            'TWO_WEEKS_AGO'   => 'TWO_WEEKS_AGO',
            'THREE_WEEKS_AGO' => 'THREE_WEEKS_AGO',
            'FOUR_WEEKS_AGO' => 'FOUR_WEEKS_AGO',

            'START_OF_WEEK_MONDAY'      => 'START_OF_WEEK_MONDAY',
            'START_OF_WEEK_SUNDAY'      => 'START_OF_WEEK_SUNDAY',
            'START_OF_LAST_WEEK_MONDAY' => 'START_OF_LAST_WEEK_MONDAY',
            'START_OF_LAST_WEEK_SUNDAY' => 'START_OF_LAST_WEEK_SUNDAY',

            'END_OF_WEEK_SUNDAY'        => 'END_OF_WEEK_SUNDAY',
            'END_OF_WEEK_SATURDAY'      => 'END_OF_WEEK_SATURDAY',
            'END_OF_LAST_WEEK_SUNDAY'   => 'END_OF_LAST_WEEK_SUNDAY',
            'END_OF_LAST_WEEK_SATURDAY' => 'END_OF_LAST_WEEK_SATURDAY',

            'START_OF_MONTH'      => 'START_OF_MONTH',
            'END_OF_MONTH'        => 'END_OF_MONTH',
            'START_OF_LAST_MONTH' => 'START_OF_LAST_MONTH',
            'END_OF_LAST_MONTH'   => 'END_OF_LAST_MONTH',

            'START_OF_QUARTER'      => 'START_OF_QUARTER',
            'END_OF_QUARTER'        => 'END_OF_QUARTER',
            'START_OF_LAST_QUARTER' => 'START_OF_LAST_QUARTER',
            'END_OF_LAST_QUARTER'   => 'END_OF_LAST_QUARTER',

            'START_OF_YEAR'      => 'START_OF_YEAR',
            'END_OF_YEAR'        => 'END_OF_YEAR',
            'START_OF_LAST_YEAR' => 'START_OF_LAST_YEAR',
            'END_OF_LAST_YEAR'   => 'END_OF_LAST_YEAR',
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
        string $value = null,
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
        return self::createDefaultData($operator, Carbon::instance($dateTime)->toRfc3339String());
    }


    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);
        $resolver->setDefaults([
            'widget'        => 'date',
            'value_choices' => self::getValueChoices(),
            'choices'       => self::getVariables(),
        ]);

        $resolver->setAllowedValues('widget', ['text', 'date', 'datetime', 'datepicker', 'datetimepicker']);
        $resolver->setAllowedTypes('value_choices', ['array']);
        $resolver->setAllowedTypes('choices', ['array']);
    }

    public function handleFilter(QueryBuilder $qb, FilterRow $filterRow): void
    {
        $i = self::$cnt++;

        $op = $this->getExpressionOperator($filterRow);
        $value = $this->getExpressionValue($filterRow);
        $field = $this->getFieldAlias($qb, $filterRow);

        if ($value) {
            if (is_string($value)) {
                $value = $this->replaceVarsInValue($value);
            }

            try {
                $date = Carbon::parse($value)->startOfDay();
            } catch (Exception $e) {
                $date = Carbon::now()->startOfDay();
            }

            if ($op === self::EXPR_EQ) {
                $endDate = $date->clone()->addDay()->startOfDay();

                $qb->andWhere($qb->expr()->gte($field, ':value_start_' . $i));
                $qb->andWhere($qb->expr()->lt($field, ':value_end_' . $i));

                $qb->setParameter('value_start_' . $i, $date);
                $qb->setParameter('value_end_' . $i, $endDate);
            } else {
                $qb->andWhere($qb->expr()->$op($field, ':value_' . $i));
                $qb->setParameter('value_' . $i, $date);
            }

        } elseif (!$this->hasExpressionValue($filterRow)) {
            $qb->andWhere($qb->expr()->$op($field));
        }
    }

    public function replaceVarsInValue(string $value): string
    {
        switch (strtoupper($value)) {
            case 'TODAY':
                return Carbon::now()->startOfDay()->toRfc3339String();
            case 'YESTERDAY':
                return Carbon::now()->subDay()->startOfDay()->toRfc3339String();
            case 'TOMORROW':
                return Carbon::now()->addDay()->startOfDay()->toRfc3339String();
            case 'DAY_AFTER_TOMORROW':
                return Carbon::now()->addDays(2)->startOfDay()->toRfc3339String();

            case 'ONE_WEEK_AGO':
                return Carbon::now()->subWeek()->startOfDay()->toRfc3339String();
            case 'TWO_WEEKS_AGO':
                return Carbon::now()->subWeeks(2)->startOfDay()->toRfc3339String();
            case 'THREE_WEEKS_AGO':
                return Carbon::now()->subWeeks(3)->startOfDay()->toRfc3339String();
            case 'FOUR_WEEKS_AGO':
                return Carbon::now()->subWeeks(4)->startOfDay()->toRfc3339String();

            case 'START_OF_WEEK_MONDAY':
                return Carbon::now()->startOfWeek(Carbon::MONDAY)->toRfc3339String();
            case 'START_OF_WEEK_SUNDAY':
                return Carbon::now()->startOfWeek(Carbon::SUNDAY)->toRfc3339String();
            case 'START_OF_LAST_WEEK_MONDAY':
                return Carbon::now()->subWeek()->startOfWeek(Carbon::MONDAY)->toRfc3339String();
            case 'START_OF_LAST_WEEK_SUNDAY':
                return Carbon::now()->subWeek()->startOfWeek(Carbon::SUNDAY)->toRfc3339String();

            case 'END_OF_WEEK_SUNDAY':
                return Carbon::now()->endOfWeek(Carbon::SUNDAY)->toRfc3339String();
            case 'END_OF_WEEK_SATURDAY':
                return Carbon::now()->endOfWeek(Carbon::SATURDAY)->toRfc3339String();
            case 'END_OF_LAST_WEEK_SUNDAY':
                return Carbon::now()->subWeek()->endOfWeek(Carbon::SUNDAY)->toRfc3339String();
            case 'END_OF_LAST_WEEK_SATURDAY':
                return Carbon::now()->subWeek()->endOfWeek(Carbon::SATURDAY)->toRfc3339String();

            case 'START_OF_MONTH':
                return Carbon::now()->startOfMonth()->toRfc3339String();
            case 'END_OF_MONTH':
                return Carbon::now()->endOfMonth()->toRfc3339String();
            case 'START_OF_LAST_MONTH':
                return Carbon::now()->subMonth()->startOfMonth()->toRfc3339String();
            case 'END_OF_LAST_MONTH':
                return Carbon::now()->subMonth()->endOfMonth()->toRfc3339String();

            case 'START_OF_QUARTER':
                return Carbon::now()->startOfQuarter()->toRfc3339String();
            case 'END_OF_QUARTER':
                return Carbon::now()->endOfQuarter()->toRfc3339String();
            case 'START_OF_LAST_QUARTER':
                return Carbon::now()->subQuarter()->startOfQuarter()->toRfc3339String();
            case 'END_OF_LAST_QUARTER':
                return Carbon::now()->subQuarter()->endOfQuarter()->toRfc3339String();

            case 'START_OF_YEAR':
                return Carbon::now()->startOfYear()->toRfc3339String();
            case 'END_OF_YEAR':
                return Carbon::now()->endOfYear()->toRfc3339String();
            case 'START_OF_LAST_YEAR':
                return Carbon::now()->subYear()->startOfYear()->toRfc3339String();
            case 'END_OF_LAST_YEAR':
                return Carbon::now()->subYear()->endOfYear()->toRfc3339String();
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
                'mapped' => false,
                'choices' => self::getValueChoices(),
                'attr' => [
                    'class' => 'custom-select'
                ],
            ])
            ->add('_variables', ChoiceType::class, [
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
            $builder->get('_variables')->setData($data->getValue());
        } else if ($valueType === self::VALUE_CHOICE_DATE) {
            $builder->get('_dateValue')->setData(Carbon::parse($data->getValue()));
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
            $date = Carbon::parse($builder->get('_dateValue')->getData());
            $data->setValue($date->toRfc3339String());
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
