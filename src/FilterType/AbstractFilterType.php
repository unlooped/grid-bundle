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

use function Symfony\Component\String\u;

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

    protected string $template = '@UnloopedGrid/filter_types/text.html.twig';

    /**
     * @var array<string, string>
     */
    protected static array $conditionMap = [
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
    protected static array $valueMap = [
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

    /**
     * @param string $baseTemplatePath #Template
     */
    public function setBaseTemplatePath(string $baseTemplatePath): void
    {
        $this->template = str_replace('@UnloopedGrid', $baseTemplatePath, $this->template);
    }

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
        if (!\in_array($operator, static::getAvailableOperators(), true)) {
            throw new OperatorDoesNotExistException($operator, static::class);
        }

        $dto           = new DefaultFilterDataStruct();
        $dto->operator = $operator;
        $dto->value    = $value;

        return $dto;
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
            'is_removable'  => true,
        ]);

        $resolver->setAllowedTypes('show_filter', ['boolean']);
        $resolver->setAllowedTypes('template', ['string']);
        $resolver->setAllowedTypes('label', ['null', 'string']);
        $resolver->setAllowedTypes('attr', 'array');
        $resolver->setAllowedTypes('widget', 'string');
        $resolver->setAllowedTypes('operators', 'array');
        $resolver->setAllowedTypes('default_data', ['null', DefaultFilterDataStruct::class]);
        $resolver->setAllowedTypes('is_removable', ['boolean']);

        $resolver->setAllowedValues('widget', ['text']);
    }

    public function handleFilter(QueryBuilder $qb, FilterRow $filterRow, array $options = []): void
    {
        $value = $this->getExpressionValue($filterRow);

        $multiple = ($options['multiple'] ?? false) === true;
        if (!($multiple && \is_array($value) && [] !== $value) && !(null !== $value && !\is_array($value)) && $this->hasExpressionValue($filterRow)) {
            return;
        }

        $op        = $this->getExpressionOperator($filterRow);
        $fieldInfo = $this->getFieldInfo($qb, $filterRow);
        $alias     = $fieldInfo->alias;

        if ($fieldInfo->fieldData && ClassMetadata::MANY_TO_MANY === $fieldInfo->fieldData['type']) {
            $newAlias = uniqid('jn', false);

            $qb->leftJoin($alias, $newAlias);
            $alias = $newAlias.'.id';
        }

        if ($multiple && \is_array($value) && [] !== $value) {
            if (self::EXPR_NEQ === $filterRow->getOperator()) {
                $andOrX = $qb->expr()->andX();
            } else {
                $andOrX = $qb->expr()->orX();
            }

            foreach ($value as $val) {
                $suffix = uniqid('', false);

                if ($fieldInfo->fieldData && ClassMetadata::INHERITANCE_TYPE_TABLE_PER_CLASS === $fieldInfo->fieldData['type']) {
                    $andOrX->add($qb->expr()->isMemberOf(':value_'.$suffix, $alias));
                } else {
                    $andOrX->add($qb->expr()->{$op}($alias, ':value_'.$suffix));
                }

                $qb->setParameter('value_'.$suffix, $val);
            }

            $qb->andWhere($andOrX);

            if (\array_key_exists('multiple_expr', $options) && 'AND' === $options['multiple_expr']) {
                $qb->groupBy($qb->getRootAliases()[0].'.id');
                $qb->having('COUNT(DISTINCT '.$alias.') = :cnt_'.$suffix);
                $qb->setParameter('cnt_'.$suffix, \count($value));
            }
        } elseif (null !== $value && !\is_array($value)) {
            $suffix = uniqid('', false);

            if ($fieldInfo->fieldData && ClassMetadata::INHERITANCE_TYPE_TABLE_PER_CLASS === $fieldInfo->fieldData['type']) {
                $qb->andWhere($qb->expr()->isMemberOf(':value_'.$suffix, $alias));
            } else {
                $qb->andWhere($qb->expr()->{$op}($alias, ':value_'.$suffix));
            }

            $qb->setParameter('value_'.$suffix, $value);
        } elseif (!$this->hasExpressionValue($filterRow)) {
            if ($filterRow->getOperator() === static::EXPR_IS_EMPTY) {
                $suffix = uniqid('', false);

                $orX = $qb->expr()->orX();
                $orX->add($qb->expr()->{$op}($alias));
                $orX->add($qb->expr()->eq($alias, ':empty_'.$suffix));
                $qb->andWhere($orX);
                $qb->setParameter('empty_'.$suffix, '');
            } else {
                $qb->andWhere($qb->expr()->{$op}($alias));
            }
        }
    }

    public function getExpressionOperator(FilterRow $filterRow): string
    {
        $condition = $filterRow->getOperator();

        if (\array_key_exists($condition, static::$conditionMap)) {
            $condition = static::$conditionMap[$condition];
        }

        return u($condition)->camel()->toString();
    }

    /**
     * @return array|string|null
     */
    public function getExpressionValue(FilterRow $filterRow)
    {
        $value    = $filterRow->getValue();
        $operator = $filterRow->getOperator();

        if (\array_key_exists($operator, static::$valueMap)) {
            $mapVal = static::$valueMap[$operator];
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
        $operator = $filterRow->getOperator();

        if (\array_key_exists($operator, static::$valueMap)) {
            $mapVal = static::$valueMap[$operator];

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

    public function preSubmitFormData($builder, array $options = [], $data = null, ?FormEvent $event = null): void
    {
        $this->buildForm($builder, $options, $data);
    }

    public function postSetFormData($builder, array $options = [], $data = null, ?FormEvent $event = null): void
    {
        $this->buildForm($builder, $options, $data);
    }

    public function postFormSubmit($builder, array $options = [], $data = null, ?FormEvent $event = null): void
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
        return static::getExprList();
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
