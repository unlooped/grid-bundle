<?php

namespace Unlooped\GridBundle\Model;

use Knp\Component\Pager\Pagination\PaginationInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Unlooped\GridBundle\Column\Column;
use Unlooped\GridBundle\Entity\Filter;
use Unlooped\GridBundle\Helper\GridHelper;
use Unlooped\GridBundle\Struct\AggregateResultStruct;

class Grid
{
    private GridHelper $gridHelper;
    private PaginationInterface $pagination;
    private FilterFormRequest $filterFormRequest;
    private ?FilterUserSettingsFormRequest $filterUserSettingsFormRequest;
    private array $filterData;
    private bool $saveFilter;
    private string $route;
    private array $routeParams;
    private array $existingFilters;
    private FormView $filterFormView;
    private ?AggregateResultStruct $aggregateResults;
    private ?FormView $filterUserSettingsFormView = null;

    public function __construct(
        GridHelper $gridHelper,
        PaginationInterface $pagination,
        FilterFormRequest $filterFormRequest,
        ?FilterUserSettingsFormRequest $filterUserSettingsFormRequest,
        array $filterData,
        bool $saveFilter = false,
        string $route = '',
        array $routeParams = [],
        array $existingFilters = [],
        ?AggregateResultStruct $aggregateResults = null
    ) {
        $this->gridHelper                    = $gridHelper;
        $this->pagination                    = $pagination;
        $this->filterFormRequest             = $filterFormRequest;
        $this->filterUserSettingsFormRequest = $filterUserSettingsFormRequest;
        $this->filterData                    = $filterData;
        $this->saveFilter                    = $saveFilter;
        $this->route                         = $route;
        $this->routeParams                   = $routeParams;
        $this->existingFilters               = $existingFilters;
        $this->aggregateResults              = $aggregateResults;

        $this->filterFormView = $filterFormRequest->getForm()->createView();
        if ($filterUserSettingsFormRequest) {
            $this->filterUserSettingsFormView = $filterUserSettingsFormRequest->getForm()->createView();
        }
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

    /**
     * @return Column[]
     */
    public function getVisibleColumns(): array
    {
        if ($this->filterUserSettingsFormRequest) {
            $filterUserSettings = $this->filterUserSettingsFormRequest->getFilterUserSettings();
            $visibleColumns     = $filterUserSettings->getVisibleColumns();
        } else {
            $visibleColumns = null;
        }

        return array_filter($this->getColumns(), static function (Column $column) use ($visibleColumns): bool {
            if (false === $column->getOption('visible')) {
                return false;
            }

            if (null !== $visibleColumns
                && true === $column->getOption('isHideable')
                && !\in_array($column->getField(), $visibleColumns, true)) {
                return false;
            }

            return true;
        });
    }

    public function getAggregateResults(): ?AggregateResultStruct
    {
        return $this->aggregateResults;
    }

    public function getFilter(): Filter
    {
        return $this->gridHelper->getFilter();
    }

    public function getFilterForm(): FormInterface
    {
        return $this->filterFormRequest->getForm();
    }

    public function getFilterFormView(): FormView
    {
        return $this->filterFormView;
    }

    public function getFilterApplied(): bool
    {
        return $this->filterFormRequest->isFilterApplied();
    }

    public function getTitle(): string
    {
        return $this->gridHelper->getTitle();
    }

    public function getListRow(): ?string
    {
        return $this->gridHelper->getListRow();
    }

    public function getAggregateRow(): ?string
    {
        return $this->gridHelper->getAggregateRow();
    }

    public function getPaginationTemplate(): string
    {
        return $this->gridHelper->getPaginationTemplate();
    }

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
        return $this->pagination->getCurrentPageNumber();
    }

    public function getCurrentPerPage(): int
    {
        return $this->pagination->getItemNumberPerPage();
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
        return $this->filterFormRequest->isFilterSaved();
    }

    public function wasFilterDeleted(): bool
    {
        return $this->filterFormRequest->isFilterDeleted();
    }

    public function getFilterUserSettingsFormView(): ?FormView
    {
        return $this->filterUserSettingsFormView;
    }

    public function userSettingsEnabled(): bool
    {
        return $this->gridHelper->getUserSettingsEnabled();
    }
}
