<?php

namespace Unlooped\GridBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="Unlooped\GridBundle\Repository\FilterRepository")
 */
class Filter
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private ?int $id = null;

    /**
     * @ORM\OneToMany(targetEntity="Unlooped\GridBundle\Entity\FilterRow", mappedBy="filter", orphanRemoval=true, cascade={"ALL"})
     *
     * @Assert\Valid()
     */
    private Collection $rows;

    /**
     * @ORM\Column(type="string")
     */
    private string $entity;

    /**
     * @ORM\Column(type="string", unique=true)
     */
    private ?string $hash = null;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private ?string $route = null;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private ?string $name = null;

    /**
     * @ORM\Column(type="boolean")
     */
    private bool $showAdvancedFilter = false;

    /**
     * @ORM\Column(type="boolean")
     */
    private bool $isDefault = false;

    private bool $hasDefaultShowFilter = false;

    /** @var array<string, string> */
    private array $fields;
    private bool $isSaveable = false;

    public function __construct(string $entity)
    {
        $this->rows   = new ArrayCollection();
        $this->entity = $entity;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return Collection<FilterRow>
     */
    public function getRows(): Collection
    {
        return $this->rows;
    }

    public function addRow(FilterRow $row): self
    {
        if (!$this->rows->contains($row)) {
            $this->rows[] = $row;
            $row->setFilter($this);
        }

        return $this;
    }

    public function removeRow(FilterRow $row): self
    {
        if ($this->rows->contains($row)) {
            $this->rows->removeElement($row);
            // set the owning side to null (unless already changed)
            if ($row->getFilter() === $this) {
                $row->setFilter(null);
            }
        }

        return $this;
    }

    public function getRowForField(string $field): ?FilterRow
    {
        $c = Criteria::create();
        $c->andWhere(Criteria::expr()->eq('field', $field));

        $res = $this->rows->matching($c);
        if ($res && $res->first()) {
            return $res->first();
        }

        return null;
    }

    public function getFields()
    {
        return $this->fields;
    }

    public function setFields($fields): self
    {
        $this->fields = $fields;

        return $this;
    }

    public function setIsSaveable(bool $isSaveable): void
    {
        $this->isSaveable = $isSaveable;
    }

    public function isSaveable(): bool
    {
        return $this->isSaveable;
    }

    public function getEntity(): string
    {
        return $this->entity;
    }

    public function setEntity(string $entity): self
    {
        $this->entity = $entity;

        return $this;
    }

    public function getHash(): ?string
    {
        return $this->hash;
    }

    public function setHash(string $hash): self
    {
        $this->hash = $hash;

        return $this;
    }

    public function getRoute(): ?string
    {
        return $this->route;
    }

    public function setRoute(string $route): self
    {
        $this->route = $route;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function isDefault(): bool
    {
        return $this->isDefault;
    }

    public function setIsDefault(bool $isDefault): self
    {
        $this->isDefault = $isDefault;

        return $this;
    }

    public function hasDefaultShowFilter(): bool
    {
        return $this->hasDefaultShowFilter;
    }

    public function setHasDefaultShowFilter(bool $hasDefaultShowFilter): self
    {
        $this->hasDefaultShowFilter = $hasDefaultShowFilter;

        return $this;
    }

    public function isShowAdvancedFilter(): bool
    {
        return $this->showAdvancedFilter;
    }

    public function setShowAdvancedFilter(bool $showAdvancedFilter): self
    {
        $this->showAdvancedFilter = $showAdvancedFilter;

        return $this;
    }
}
