<?php

namespace Unlooped\GridBundle\Model;

use Knp\Component\Pager\Pagination\PaginationInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Unlooped\GridBundle\Column\Column;
use Unlooped\GridBundle\Entity\Filter;
use Unlooped\GridBundle\Helper\GridHelper;

class Grid
{
    private GridHelper $gridHelper;
    private PaginationInterface $pagination;
    private FormInterface $filterForm;
    private FormView $filterFormView;
    private bool $filterApplied;
    private string $route;
    private array $routeParams;
    private int $currentPage;
    private int $currentPerPage;
    private bool $saveFilter;
    private array $existingFilters;
    private bool $filterSaved;
    private bool $filterDeleted;
    private array $filterData;

    public function __construct(
        GridHelper $gridHelper,
        PaginationInterface $pagination,
        FormInterface $filterForm,
        int $currentPage,
        int $currentPerPage,
        array $filterData,
        bool $saveFilter = false,
        bool $filterApplied = false,
        bool $filterSaved = false,
        bool $filterDeleted = false,
        string $route = '',
        array $routeParams = [],
        array $existingFilters = []
    ) {
        $this->gridHelper      = $gridHelper;
        $this->pagination      = $pagination;
        $this->filterForm      = $filterForm;
        $this->filterFormView  = $filterForm->createView();
        $this->filterApplied   = $filterApplied;
        $this->currentPage     = $currentPage;
        $this->currentPerPage  = $currentPerPage;
        $this->filterData      = $filterData;
        $this->route           = $route;
        $this->routeParams     = $routeParams;
        $this->saveFilter      = $saveFilter;
        $this->existingFilters = $existingFilters;
        $this->filterSaved     = $filterSaved;
        $this->filterDeleted   = $filterDeleted;
    }

    public function getPagination(): PaginationInterface
    {
        return $this->pagination;
    }

    public function getHelper(): GridHelper
    {
        return $this->gridHelper;
    }

    /**
     * @return Column[]
     */
    public function getColumns(): array
    {
        return $this->gridHelper->getColumns();
    }

    public function getFilter(): Filter
    {
        return $this->gridHelper->getFilter();
    }

    public function getFilterForm(): FormInterface
    {
        return $this->filterForm;
    }

    public function getFilterFormView(): FormView
    {
        return $this->filterFormView;
    }

    public function getFilterApplied(): bool
    {
        return $this->filterApplied;
    }

    public function getTitle(): string
    {
        return $this->gridHelper->getTitle();
    }

    /**
     * @deprecated
     */
    public function getListRow(): ?string
    {
        return $this->gridHelper->getListRow();
    }

    public function getPaginationTemplate(): string
    {
        return $this->gridHelper->getPaginationTemplate();
    }

    /**
     * @deprecated
     */
    public function getListHeaderTemplate(): string
    {
        return $this->gridHelper->getListHeaderTemplate();
    }

    public function getFilterView(): string
    {
        return $this->gridHelper->getFilterView();
    }

    public function getListView(): string
    {
        return $this->gridHelper->getListView();
    }

    public function getCreateRoute(): ?string
    {
        return $this->gridHelper->getCreateRoute();
    }

    public function getCreateLabel(): ?string
    {
        return $this->gridHelper->getCreateLabel();
    }

    public function getCurrentPage(): int
    {
        return $this->currentPage;
    }

    public function getCurrentPerPage(): int
    {
        return $this->currentPerPage;
    }

    public function getRoute(): string
    {
        return $this->route;
    }

    public function getRouteParams(): array
    {
        return $this->routeParams;
    }

    public function isSaveFilter(): bool
    {
        return $this->saveFilter;
    }

    /**
     * @return Filter[]
     */
    public function getExistingFilters(): array
    {
        return $this->existingFilters;
    }

    /**
     * @param Filter[] $existingFilters
     */
    public function setExistingFilters(array $existingFilters): void
    {
        $this->existingFilters = $existingFilters;
    }

    public function getFilterData(): ?array
    {
        return $this->filterData;
    }

    public function getFiltersAsJson(): string
    {
        return json_encode($this->filterData);
    }

    public function wasFilterSaved(): bool
    {
        return $this->filterSaved;
    }

    public function wasFilterDeleted(): bool
    {
        return $this->filterDeleted;
    }
}
