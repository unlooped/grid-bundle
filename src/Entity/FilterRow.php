<?php

namespace Unlooped\GridBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="Unlooped\GridBundle\Repository\FilterRowRepository")
 */
class FilterRow
{
    /**
     * @ORM\Id()
     *
     * @ORM\GeneratedValue()
     *
     * @ORM\Column(type="integer")
     */
    private ?int $id = null;

    /**
     * @ORM\ManyToOne(targetEntity="Unlooped\GridBundle\Entity\Filter", inversedBy="rows")
     *
     * @ORM\JoinColumn(nullable=false)
     */
    private ?Filter $filter = null;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private ?string $field = null;

    /**
     * @ORM\Column(type="json", nullable=true)
     */
    private array $metaData = [];

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

    public function getMetaData(): ?array
    {
        return $this->metaData;
    }

    public function setMetaData(array $metaData): self
    {
        $this->metaData = $metaData;

        return $this;
    }

    public function addMetaData(string $key, $value): self
    {
        $this->metaData[$key] = $value;

        return $this;
    }

    public function getOperator(): ?string
    {
        return $this->metaData['operator'] ?? null;
    }

    public function setOperator(string $operator): self
    {
        $this->addMetaData('operator', $operator);

        return $this;
    }

    /**
     * @return mixed|mixed[]
     */
    public function getValue()
    {
        return $this->metaData['value'] ?? null;
    }

    /**
     * @param mixed|mixed[] $value
     */
    public function setValue($value): self
    {
        $this->addMetaData('value', $value);

        return $this;
    }
}
