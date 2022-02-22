<?php

namespace Unlooped\GridBundle\Model;

use Symfony\Component\Form\FormInterface;

class FilterFormRequest
{
    private bool $isFilterApplied = false;
    private bool $isFilterSaved   = false;
    private bool $isFilterDeleted = false;
    private FormInterface $filterForm;

    public function __construct(FormInterface $filterForm)
    {
        $this->filterForm = $filterForm;
    }

    public function isFilterApplied(): bool
    {
        return $this->isFilterApplied;
    }

    public function setIsFilterApplied(bool $isFilterApplied): self
    {
        $this->isFilterApplied = $isFilterApplied;

        return $this;
    }

    public function isFilterSaved(): bool
    {
        return $this->isFilterSaved;
    }

    public function setIsFilterSaved(bool $isFilterSaved): self
    {
        $this->isFilterSaved = $isFilterSaved;

        return $this;
    }

    public function isFilterDeleted(): bool
    {
        return $this->isFilterDeleted;
    }

    public function setIsFilterDeleted(bool $isFilterDeletes): self
    {
        $this->isFilterDeleted = $isFilterDeletes;

        return $this;
    }

    public function getForm(): FormInterface
    {
        return $this->filterForm;
    }
}
