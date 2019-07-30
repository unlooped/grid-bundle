<?php


namespace Unlooped\GridBundle\FilterType;


use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Unlooped\GridBundle\Entity\FilterRow;
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
            'template'   => $this->template,
            'label'      => null,
            'attr'       => [],
            'widget'     => 'text',
        ]);

        $resolver->setAllowedTypes('template', ['string']);
        $resolver->setAllowedTypes('label', ['null', 'string']);
        $resolver->setAllowedTypes('attr', 'array');
        $resolver->setAllowedTypes('widget', 'string');

        $resolver->setAllowedValues('widget', ['text']);
    }

    public function handleFilter(QueryBuilder $qb, FilterRow $filterRow): void
    {
        $i = self::$cnt++;

        $op = $this->getExpressionOperator($filterRow);
        $value = $this->getExpressionValue($filterRow);
        $field = $this->getFieldAlias($qb, $filterRow);
        if ($value) {
            $qb->andWhere($qb->expr()->$op($field, ':value_' . $i));
            $qb->setParameter('value_' . $i, $value);
        } else {
            $qb->andWhere($qb->expr()->$op($field));
        }
    }

    protected function getFieldAlias(QueryBuilder $qb, FilterRow $filterRow)
    {
        $classMetadataFactory = $qb->getEntityManager()->getMetadataFactory();
        /** @var ClassMetadataInfo $md */
        $md = $classMetadataFactory->getMetadataFor($filterRow->getFilter()->getEntity());

        $fields = explode('.', $filterRow->getField());
        $alias = $qb->getRootAliases()[0];

        if (count($fields) === 1) {
            return $alias . '.' . $fields[0];
        }

        foreach ($fields as $field) {
            if ($md->hasAssociation($field)) {
                $nAlias = $alias . '_' . $field;
                $qb->leftJoin($alias . '.' . $field, $nAlias);
                $alias = $nAlias;

                $md = $classMetadataFactory->getMetadataFor($md->getAssociationMapping($field)['targetEntity']);
                continue;
            }

            $alias .= '.' . $field;

            break;
        }

        return $alias;
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

            $value = $mapVal['prefix'].$value.$mapVal['suffix'];
        }

        return $value;
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
            ->add('value')
        ;
    }

    public function postSetFormData(FormEvent $event): void
    {
        // nothing to do here
    }

    public function postFormSubmit(FormEvent $event)
    {
        // nothing to do here
    }
}
