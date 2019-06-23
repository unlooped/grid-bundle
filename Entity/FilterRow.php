<?php

namespace Unlooped\GridBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Unlooped\Helper\ConstantHelper;
use Unlooped\Helper\StringHelper;

/**
 * @ORM\Entity(repositoryClass="Unlooped\GridBundle\Repository\FilterRowRepository")
 */
class FilterRow
{

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

    private static $conditionMap = [
        self::EXPR_CONTAINS     => self::EXPR_LIKE,
        self::EXPR_NOT_CONTAINS => self::EXPR_NOT_LIKE,
        self::EXPR_BEGINS_WITH  => self::EXPR_LIKE,
        self::EXPR_ENDS_WITH    => self::EXPR_LIKE,
        self::EXPR_IS_EMPTY     => self::IEXPR_IS_NULL,
        self::EXPR_IS_NOT_EMPTY => self::IEXPR_IS_NOT_NULL,
    ];

    private static $valueMap = [
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
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="Unlooped\GridBundle\Entity\Filter", inversedBy="rows")
     * @ORM\JoinColumn(nullable=false)
     */
    private $filter;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $field;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $operator = self::EXPR_CONTAINS;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $value;

    public static function getExprList(): array
    {
        return ConstantHelper::getList('EXPR');
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFilter(): ?Filter
    {
        return $this->filter;
    }

    public function setFilter(?Filter $filter): self
    {
        $this->filter = $filter;

        return $this;
    }

    public function getField(): ?string
    {
        return $this->field;
    }

    public function setField(string $field): self
    {
        $this->field = $field;

        return $this;
    }

    public function getOperator(): ?string
    {
        return $this->operator;
    }

    public function getExpressionOperator(): string
    {
        $condition = $this->getOperator();
        if (array_key_exists($condition, self::$conditionMap)) {
            $condition = self::$conditionMap[$this->getOperator()];
        }

        return StringHelper::camelize($condition)->toString();
    }

    /**
     * @return array|string|null
     */
    public function getExpressionValue()
    {
        $value = $this->getValue();
        if (array_key_exists($this->getOperator(), self::$valueMap)) {
            $mapVal = self::$valueMap[$this->getOperator()];
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

    public function setOperator(string $operator): self
    {
        $this->operator = $operator;

        return $this;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function setValue(string $value): self
    {
        $this->value = $value;

        return $this;
    }

}
