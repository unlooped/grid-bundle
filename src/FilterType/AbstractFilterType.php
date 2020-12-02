<?php

namespace Unlooped\GridBundle\FilterType;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\Mapping\MappingException;
use ReflectionException;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Unlooped\GridBundle\Entity\FilterRow;
use Unlooped\GridBundle\Exception\OperatorDoesNotExistException;
use Unlooped\GridBundle\Helper\RelationsHelper;
use Unlooped\GridBundle\Struct\DefaultFilterDataStruct;
use Unlooped\GridBundle\Struct\FieldMetaDataStruct;
use Unlooped\Helper\ConstantHelper;
use Unlooped\Helper\StringHelper;

abstract class AbstractFilterType implements FilterType
{
    public const EXPR_CONTAINS     = 'contains';
    public const EXPR_NOT_CONTAINS = 'not_contains';
    public const EXPR_EQ           = 'eq';
    public const EXPR_NEQ          = 'neq';
    public const EXPR_LT           = 'lt';
    public const EXPR_LTE          = 'lte';
    public const EXPR_GT           = 'gt';
    public const EXPR_GTE          = 'gte';
    public const EXPR_LIKE         = 'like';
    public const EXPR_NOT_LIKE     = 'not_like';
    public const EXPR_BEGINS_WITH  = 'begins_with';
    public const EXPR_ENDS_WITH    = 'ends_with';
    public const EXPR_IS_EMPTY     = 'is_empty';
    public const EXPR_IS_NOT_EMPTY = 'is_not_empty';
    public const EXPR_IN           = 'in';
    public const EXPR_IN_RANGE     = 'in_range';

    public const IEXPR_IS_NULL     = 'is_null';
    public const IEXPR_IS_NOT_NULL = 'is_not_null';

    protected $template  = '@UnloopedGrid/filter_types/text.html.twig';

    /**
     * @var array<string, string>
     */
    protected static $conditionMap = [
        self::EXPR_CONTAINS     => self::EXPR_LIKE,
        self::EXPR_NOT_CONTAINS => self::EXPR_NOT_LIKE,
        self::EXPR_BEGINS_WITH  => self::EXPR_LIKE,
        self::EXPR_ENDS_WITH    => self::EXPR_LIKE,
        self::EXPR_IS_EMPTY     => self::IEXPR_IS_NULL,
        self::EXPR_IS_NOT_EMPTY => self::IEXPR_IS_NOT_NULL,
    ];

    /**
     * @var array<string, array<string, mixed>>
     */
    protected static $valueMap = [
        self::EXPR_CONTAINS     => [
            'prefix' => '%',
            'suffix' => '%',
            'value'  => true,
        ],
        self::EXPR_NOT_CONTAINS => [
            'prefix' => '%',
            'suffix' => '%',
            'value'  => true,
        ],
        self::EXPR_BEGINS_WITH  => [
            'prefix' => '',
            'suffix' => '%',
            'value'  => true,
        ],
        self::EXPR_ENDS_WITH    => [
            'prefix' => '%',
            'suffix' => '',
            'value'  => true,
        ],
        self::EXPR_IS_EMPTY     => [
            'value' => false,
        ],
        self::EXPR_IS_NOT_EMPTY => [
            'value' => false,
        ],
        self::EXPR_IN           => [
            'split' => true,
        ],
    ];

    public static function getVariables(): array
    {
        return [];
    }

    public static function getExprList(): array
    {
        return ConstantHelper::getList('EXPR');
    }

    /**
     * @param mixed|null $value
     *
     * @throws OperatorDoesNotExistException
     */
    public static function createDefaultData(string $operator, $value = null): DefaultFilterDataStruct
    {
        if (!\in_array($operator, self::getAvailableOperators(), true)) {
            throw new OperatorDoesNotExistException($operator, self::class);
        }

        $dataStruct           = new DefaultFilterDataStruct();
        $dataStruct->operator = $operator;
        $dataStruct->value    = $value;

        return $dataStruct;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'show_filter'   => false,
            'default_data'  => null,
            'template'      => $this->template,
            'label'         => null,
            'attr'          => [],
            'widget'        => 'text',
            'operators'     => static::getAvailableOperators(),
        ]);

        $resolver->setAllowedTypes('show_filter', ['boolean']);
        $resolver->setAllowedTypes('template', ['string']);
        $resolver->setAllowedTypes('label', ['null', 'string']);
        $resolver->setAllowedTypes('attr', 'array');
        $resolver->setAllowedTypes('widget', 'string');
        $resolver->setAllowedTypes('operators', 'array');
        $resolver->setAllowedTypes('default_data', ['null', DefaultFilterDataStruct::class]);

        $resolver->setAllowedValues('widget', ['text']);
    }

    public function handleFilter(QueryBuilder $qb, FilterRow $filterRow, array $options = []): void
    {
        $op    = $this->getExpressionOperator($filterRow);
        $value = $this->getExpressionValue($filterRow);

        $fieldInfo = $this->getFieldInfo($qb, $filterRow);
        $alias     = $fieldInfo->alias;

        if (null !== $value) {
            $suffix = uniqid('', false);

            if ($fieldInfo->fieldData && ClassMetadata::INHERITANCE_TYPE_TABLE_PER_CLASS === $fieldInfo->fieldData['type']) {
                $qb->andWhere($qb->expr()->isMemberOf(':value_'.$suffix, $alias));
            } else {
                $qb->andWhere($qb->expr()->{$op}($alias, ':value_'.$suffix));
            }
            $qb->setParameter('value_'.$suffix, $value);
        } elseif (!$this->hasExpressionValue($filterRow)) {
            $qb->andWhere($qb->expr()->{$op}($alias));
        }
    }

    public function getExpressionOperator(FilterRow $filterRow): string
    {
        $condition = $filterRow->getOperator();
        if (\array_key_exists($condition, self::$conditionMap)) {
            $condition = self::$conditionMap[$filterRow->getOperator()];
        }

        return StringHelper::camelize($condition)->toString();
    }

    /**
     * @return array|string|null
     */
    public function getExpressionValue(FilterRow $filterRow)
    {
        $value = $filterRow->getValue();
        if (\array_key_exists($filterRow->getOperator(), self::$valueMap)) {
            $mapVal = self::$valueMap[$filterRow->getOperator()];
            if (\array_key_exists('split', $mapVal) && $mapVal['split']) {
                return array_map('trim', explode(',', $value));
            }

            if (!$mapVal['value']) {
                return null;
            }

            if (!$value) {
                return null;
            }

            $value = $mapVal['prefix'].$value.$mapVal['suffix'];
        }

        return $value;
    }

    public function hasExpressionValue(FilterRow $filterRow): bool
    {
        if (\array_key_exists($filterRow->getOperator(), self::$valueMap)) {
            $mapVal = self::$valueMap[$filterRow->getOperator()];

            return $mapVal['value'];
        }

        return true;
    }

    public function buildForm($builder, array $options = [], $data = null): void
    {
        $builder
            ->add('value', null, ['required' => false])
        ;
    }

    public function preSubmitFormData($builder, array $options = [], $data = null, FormEvent $event = null): void
    {
        $this->buildForm($builder, $options, $data);
    }

    public function postSetFormData($builder, array $options = [], $data = null, FormEvent $event = null): void
    {
        $this->buildForm($builder, $options, $data);
    }

    public function postFormSubmit($builder, array $options = [], $data = null, FormEvent $event = null): void
    {
        // nothing to do here
    }

    public function getFormFieldNames(): array
    {
        return ['value'];
    }

    /**
     * @return array<string, string>
     */
    protected static function getAvailableOperators(): array
    {
        return self::getExprList();
    }

    /**
     * @throws ReflectionException
     * @throws MappingException
     */
    protected function getFieldInfo(QueryBuilder $qb, FilterRow $filterRow): FieldMetaDataStruct
    {
        $filter = $filterRow->getFilter();

        \assert(null !== $filter);

        return RelationsHelper::joinRequiredPaths($qb, $filter->getEntity(), $filterRow->getField());
    }
}
