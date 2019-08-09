<?php

namespace Unlooped\GridBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Unlooped\GridBundle\FilterType\FilterType;

/**
 * @ORM\Entity(repositoryClass="Unlooped\GridBundle\Repository\FilterRowRepository")
 */
class FilterRow
{

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
    private $operator = FilterType::EXPR_CONTAINS;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $value;

    /**
     * @var array
     *
     * @ORM\Column(type="json_array", nullable=true)
     */
    private $metaData;

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

    public function setOperator(string $operator): self
    {
        $this->operator = $operator;

        return $this;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function setValue($value): self
    {
        $this->value = $value;

        return $this;
    }

    public function getMetaData(): ?array
    {
        return $this->metaData;
    }

    public function setMetaData(array $metaData): self
    {
        $this->metaData = $metaData;

        return $this;
    }

}
