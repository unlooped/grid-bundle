<?php

namespace Unlooped\GridBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Unlooped\GridBundle\Column\Column;
use Unlooped\GridBundle\Repository\FilterUserSettingsRepository;

/**
 * @ORM\Entity(repositoryClass=FilterUserSettingsRepository::class)
 *
 * @ORM\Table(uniqueConstraints={@ORM\UniqueConstraint(columns={"filter_hash", "user_identifier", "route"})})
 */
class FilterUserSettings
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private ?int $id = null;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private ?string $userIdentifier;

    /**
     * @ORM\Column(type="json")
     */
    private array $visibleColumns = [];

    /**
     * @ORM\Column(type="string", length=255)
     */
    private string $filterHash;
    /**
     * @ORM\Column(type="string", length=255)
     */
    private string $route;

    /**
     * @param string|null $userIdentifier
     */
    public function __construct(string $route, ?string $filterHash = '_default', ?string $userIdentifier = '_default')
    {
        $this->route          = $route;
        $this->filterHash     = $filterHash ?? '_default';
        $this->userIdentifier = $userIdentifier ?? '_default';
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRoute(): string
    {
        return $this->route;
    }

    public function setRoute(string $route): self
    {
        $this->route = $route;

        return $this;
    }

    public function getUserIdentifier(): ?string
    {
        return $this->userIdentifier;
    }

    public function setUserIdentifier(string $userIdentifier): self
    {
        $this->userIdentifier = $userIdentifier;

        return $this;
    }

    public function getVisibleColumns(): ?array
    {
        return $this->visibleColumns;
    }

    public function setVisibleColumns(array $visibleColumns): self
    {
        $this->visibleColumns = $visibleColumns;

        return $this;
    }

    public function addVisibleColumn(Column $column)
    {
        if (!in_array($column, $this->visibleColumns, true)) {
            $this->visibleColumns[] = $column;
        }
    }

    public function getFilterHash(): ?string
    {
        return $this->filterHash;
    }

    public function setFilterHash(?string $filterHash): self
    {
        $this->filterHash = $filterHash;
        return $this;
    }

}
