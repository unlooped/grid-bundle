<?php


namespace Unlooped\GridBundle\FilterType;


use App\Exception\OperatorDoesNotExistException;
use Doctrine\Common\Persistence\Mapping\MappingException;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Doctrine\ORM\QueryBuilder;
use ReflectionException;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Unlooped\GridBundle\Entity\FilterRow;
use Unlooped\GridBundle\Struct\DefaultFilterDataStruct;
use Unlooped\GridBundle\Struct\FieldMetaDataStruct;
use Unlooped\Helper\ConstantHelper;
use Unlooped\Helper\StringHelper;

class FilterType
{
    protected $template = '@UnloopedGrid/filter_types/text.html.twig';

    protected $field;
    protected $options;

    protected static $cnt = 0;

    public const EXPR_CONTAINS = 'contains';
    public const EXPR_NOT_CONTAINS = 'not_contains';
    public const EXPR_EQ = 'eq';
    public const EXPR_NEQ = 'neq';
    public const EXPR_LT = 'lt';
    public const EXPR_LTE = 'lte';
    public const EXPR_GT = 'gt';
    public const EXPR_GTE = 'gte';
    public const EXPR_LIKE = 'like';
    public const EXPR_NOT_LIKE = 'not_like';
    public const EXPR_BEGINS_WITH = 'begins_with';
    public const EXPR_ENDS_WITH = 'ends_with';
    public const EXPR_IS_EMPTY = 'is_empty';
    public const EXPR_IS_NOT_EMPTY = 'is_not_empty';
    public const EXPR_IN = 'in';

    public const IEXPR_IS_NULL = 'is_null';
    public const IEXPR_IS_NOT_NULL = 'is_not_null';

    protected static $conditionMap = [
        self::EXPR_CONTAINS     => self::EXPR_LIKE,
        self::EXPR_NOT_CONTAINS => self::EXPR_NOT_LIKE,
        self::EXPR_BEGINS_WITH  => self::EXPR_LIKE,
        self::EXPR_ENDS_WITH    => self::EXPR_LIKE,
        self::EXPR_IS_EMPTY     => self::IEXPR_IS_NULL,
        self::EXPR_IS_NOT_EMPTY => self::IEXPR_IS_NOT_NULL,
    ];

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

    /** @var FieldMetaDataStruct[] */
    protected static $fieldAliases = [];

    public static function getVariables(): array
    {
        return [];
    }

    public static function getExprList(): array
    {
        return ConstantHelper::getList('EXPR');
    }


    public static function getAvailableOperators(): array
    {
        return self::getExprList();
    }

    /**
     * @throws OperatorDoesNotExistException
     */
    public static function createDefaultData(string $operator, string $value = null): DefaultFilterDataStruct
    {
        if (!in_array($operator, self::getAvailableOperators(), true)) {
            throw new OperatorDoesNotExistException($operator, self::class);
        }

        $dfds = new DefaultFilterDataStruct();
        $dfds->operator = $operator;
        $dfds->value = $value;

        return $dfds;
    }

    public function __construct(string $field, array $options = [])
    {
        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);
        $this->options = $resolver->resolve($options);

        $this->field = $field;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'show_filter' => false,
            'default_data' => null,
            'template'    => $this->template,
            'label'       => null,
            'attr'        => [],
            'widget'      => 'text',
        ]);

        $resolver->setAllowedTypes('show_filter', ['boolean']);
        $resolver->setAllowedTypes('template', ['string']);
        $resolver->setAllowedTypes('label', ['null', 'string']);
        $resolver->setAllowedTypes('attr', 'array');
        $resolver->setAllowedTypes('widget', 'string');
        $resolver->setAllowedTypes('default_data', ['null', DefaultFilterDataStruct::class]);

        $resolver->setAllowedValues('widget', ['text']);
    }

    public function handleFilter(QueryBuilder $qb, FilterRow $filterRow): void
    {
        $i = self::$cnt++;

        $op = $this->getExpressionOperator($filterRow);
        $value = $this->getExpressionValue($filterRow);

        $fieldInfo = $this->getFieldInfo($qb, $filterRow);
        $alias = $fieldInfo->alias;

        if ($value) {
            if ($fieldInfo->fieldData && $fieldInfo->fieldData['type'] === ClassMetadata::INHERITANCE_TYPE_TABLE_PER_CLASS) {
                $qb->andWhere($qb->expr()->isMemberOf(':value_' . $i, $alias));
            } else {
                $qb->andWhere($qb->expr()->$op($alias, ':value_' . $i));
            }
            $qb->setParameter('value_' . $i, $value);
        } elseif (!$this->hasExpressionValue($filterRow)) {
            $qb->andWhere($qb->expr()->$op($alias));
        }
    }

    protected function getFieldInfo(QueryBuilder $qb, FilterRow $filterRow): FieldMetaDataStruct
    {
        $entity = $filterRow->getFilter()->getEntity();

        $keyPrefix = $entity . '::';
        $key = $keyPrefix . $filterRow->getField();
        if (array_key_exists($key, self::$fieldAliases)) {
            return self::$fieldAliases[$key];
        }

        $fields = explode('.', $filterRow->getField());
        $alias = $qb->getRootAliases()[0];

        $md = $this->getMetadataForEntity($qb, $entity);

        if (count($fields) === 1) {
            $fieldData = null;
            if ($md->hasAssociation($fields[0])) {
                $fieldData = $md = $md->getAssociationMapping($fields[0]);
            }
            return FieldMetaDataStruct::create($alias . '.' . $fields[0], $fieldData);
        }

        foreach ($fields as $field) {
            if ($md->hasAssociation($field)) {
                $nAlias = $alias . '_' . $field;
                $associationMapping = $md->getAssociationMapping($field);
                $md = $this->getMetadataForEntity($qb, $associationMapping['targetEntity']);

                if (array_key_exists($keyPrefix . $nAlias, self::$fieldAliases)) {
                    $alias = self::$fieldAliases[$keyPrefix . $nAlias]->alias;
                } else {
                    $qb->leftJoin($alias . '.' . $field, $nAlias);
                    self::$fieldAliases[$keyPrefix . $nAlias] = FieldMetaDataStruct::create($nAlias, $associationMapping);
                    $alias = $nAlias;
                }
                continue;
            }

            $alias .= '.' . $field;

            break;
        }

        $fmds = FieldMetaDataStruct::create($alias);

        self::$fieldAliases[$key] = $fmds;

        return $fmds;
    }

    /**
     * @param QueryBuilder $qb
     * @param $entity
     * @return ClassMetadata
     * @throws MappingException
     * @throws ReflectionException
     */
    protected function getMetadataForEntity(QueryBuilder $qb, $entity): ClassMetadata
    {
        /** @var EntityManager $em */
        $em = $qb->getEntityManager();
        /** @var ClassMetadataFactory $classMetadataFactory */
        $classMetadataFactory = $em->getMetadataFactory();

        return $classMetadataFactory->getMetadataFor($entity);
    }

    public function getExpressionOperator(FilterRow $filterRow): string
    {
        $condition = $filterRow->getOperator();
        if (array_key_exists($condition, self::$conditionMap)) {
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
        if (array_key_exists($filterRow->getOperator(), self::$valueMap)) {
            $mapVal = self::$valueMap[$filterRow->getOperator()];
            if (array_key_exists('split', $mapVal) && $mapVal['split']) {
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
        if (array_key_exists($filterRow->getOperator(), self::$valueMap)) {
            $mapVal = self::$valueMap[$filterRow->getOperator()];
            return $mapVal['value'];
        }

        return true;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function getTemplate(): string
    {
        return $this->options['template'];
    }

    /**
     * @param FormBuilderInterface|FormInterface $builder
     * @param array $options
     * @param null $data
     */
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

    public function getField(): string
    {
        return $this->field;
    }
}
