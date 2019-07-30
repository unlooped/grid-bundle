<?php


namespace Unlooped\GridBundle\FilterType;


use Carbon\Carbon;
use Doctrine\ORM\QueryBuilder;
use Exception;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Unlooped\GridBundle\Entity\FilterRow;
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

    public function postSetFormData(FormEvent $event): void
    {
        if (null !== $event->getData()) {
            $form = $event->getForm();
            /** @var FilterRow $data */
            $data = $event->getData();

            $this->buildForm($form, [], $data);

            $valueType = $data->getMetaData()['value_type'];

            $form->get('_valueChoices')->setData($valueType);

            if ($valueType === self::VALUE_CHOICE_VARIABLES) {
                $form->get('_variables')->setData($data->getValue());
            } else if ($valueType === self::VALUE_CHOICE_DATE) {
                $form->get('_dateValue')->setData(Carbon::parse($data->getValue()));
            }
        }
    }

    public function postFormSubmit(FormEvent $event): void
    {
        if (null !== $event->getData()) {
            /** @var FilterRow $data */
            $data = $event->getData();
            $form = $event->getForm();

            $valueType = $form->get('_valueChoices')->getData();
            if ($valueType === self::VALUE_CHOICE_DATE) {
                $date = Carbon::parse($form->get('_dateValue')->getData());
                $data->setValue($date->toRfc3339String());
                $data->setMetaData(['value_type' => $valueType]);
            } else if ($valueType === self::VALUE_CHOICE_VARIABLES) {
                $data->setValue($form->get('_variables')->getData());
                $data->setMetaData([
                    'value_type' => $valueType,
                    'variable' => $data->getValue(),
                ]);
            }
        }
    }
}
