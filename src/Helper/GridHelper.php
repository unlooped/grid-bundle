<?php

namespace Unlooped\GridBundle\Helper;

use Doctrine\ORM\QueryBuilder;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Unlooped\GridBundle\Column\Column;
use Unlooped\GridBundle\Column\Registry\ColumnRegistry;
use Unlooped\GridBundle\ColumnType\AbstractColumnType;
use Unlooped\GridBundle\ColumnType\ColumnTypeInterface;
use Unlooped\GridBundle\ColumnType\TextColumn;
use Unlooped\GridBundle\Entity\Filter as FilterEntity;
use Unlooped\GridBundle\Entity\FilterRow;
use Unlooped\GridBundle\Exception\DuplicateColumnException;
use Unlooped\GridBundle\Exception\DuplicateFilterException;
use Unlooped\GridBundle\Exception\TypeNotAColumnException;
use Unlooped\GridBundle\Exception\TypeNotAFilterException;
use Unlooped\GridBundle\Filter\Filter;
use Unlooped\GridBundle\Filter\Registry\FilterRegistry;
use Unlooped\GridBundle\FilterType\AutocompleteFilterType;
use Unlooped\GridBundle\FilterType\AutocompleteTextFilterType;
use Unlooped\GridBundle\FilterType\DefaultFilterType;
use Unlooped\GridBundle\FilterType\FilterType;
use Unlooped\GridBundle\Struct\DefaultFilterDataStruct;

class GridHelper
{
    private QueryBuilder $queryBuilder;
    private ColumnRegistry $columnRegistry;
    private FilterRegistry $filterRegistry;
    private ?string $name    = null;
    private int $defaultPage = 1;

    /** @var Column[] */
    private array $columns = [];

    /** @var string[] */
    private array $columnNames = [];

    private ?FilterEntity $filter;

    /** @var Filter[] */
    private array $filters = [];

    /** @var Filter[] */
    private array $defaultShowFilters = [];

    /** @var array<string, string> */
    private array $filterNames = [];

    /** @var array<string, mixed> */
    private array $options;

    private string $alias;

    public function __construct(
        QueryBuilder $queryBuilder,
        ColumnRegistry $columnRegistry,
        FilterRegistry $filterRegistry,
        array $options = [],
        FilterEntity $filter = null
    ) {
        $this->queryBuilder   = $queryBuilder;
        $this->columnRegistry = $columnRegistry;
        $this->filterRegistry = $filterRegistry;
        $this->alias          = $this->queryBuilder->getRootAliases()[0];
        $this->filter         = $filter;

        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);

        $this->options = $resolver->resolve($options);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'name'                    => '',
            'allow_duplicate_columns' => false,
            'listRow'                 => '@UnloopedGrid/list_row.html.twig',
            'paginationTemplate'      => '@UnloopedGrid/_pagination.html.twig',
            'listHeaderTemplate'      => '@UnloopedGrid/column_headers.html.twig',
            'filter_view'             => '@UnloopedGrid/_filter.html.twig',
            'list_view'               => '@UnloopedGrid/_list.html.twig',
            'title'                   => '',
            'createRoute'             => null,
            'createLabel'             => null,
            'defaultPerPage'          => 24,
            'perPageOptions'          => [24, 48, 72, 96, 120, 144, 168, 192],
            'pageParameterName'       => 'page',
            'perPageParameterName'    => 'perPage',
            'wrapQueries'             => true,
            'distinctQuery'           => true,
            'allow_save_filter'       => true,
            'current_user_identifier' => null,
            'user_settings_enabled'   => false,
        ]);

        $resolver->setAllowedTypes('name', 'string');
        $resolver->setAllowedTypes('allow_duplicate_columns', 'bool');
        $resolver->setAllowedTypes('listRow', 'string');
        $resolver->setAllowedTypes('paginationTemplate', 'string');
        $resolver->setAllowedTypes('listHeaderTemplate', 'string');
        $resolver->setAllowedTypes('filter_view', 'string');
        $resolver->setAllowedTypes('list_view', 'string');
        $resolver->setAllowedTypes('title', 'string');
        $resolver->setAllowedTypes('createRoute', ['null', 'string']);
        $resolver->setAllowedTypes('createLabel', ['null', 'string']);
        $resolver->setAllowedTypes('defaultPerPage', ['null', 'int']);
        $resolver->setAllowedTypes('perPageOptions', 'array');
        $resolver->setAllowedTypes('pageParameterName', 'string');
        $resolver->setAllowedTypes('perPageParameterName', 'string');
        $resolver->setAllowedTypes('wrapQueries', 'bool');
        $resolver->setAllowedTypes('distinctQuery', 'bool');
        $resolver->setAllowedTypes('allow_save_filter', 'bool');
        $resolver->setAllowedTypes('current_user_identifier', ['null', 'int', 'string']);
        $resolver->setAllowedTypes('user_settings_enabled', ['bool']);

        $resolver->setRequired(['title', 'listRow']);
    }

    /**
     * @throws DuplicateColumnException
     * @throws TypeNotAColumnException
     *
     * @phpstan-param class-string<ColumnTypeInterface>|null $type
     */
    public function addColumn(string $identifier, ?string $type = null, array $options = []): self
    {
        $type ??= TextColumn::class;

        if (false === $this->options['allow_duplicate_columns'] && \in_array($identifier, $this->columnNames, true)) {
            throw new DuplicateColumnException('Column '.$identifier.' already exists in '.$this->name.' Grid Helper');
        }

        if (!is_a($type, AbstractColumnType::class, true)) {
            throw new TypeNotAColumnException($type);
        }

        $alias = RelationsHelper::getAliasForEntityAndField(
            $this->getQueryBuilder(),
            $this->filter->getEntity(),
            $identifier
        );

        $this->columnNames[] = $identifier;

        $this->columns[] = new Column($identifier, $this->columnRegistry->getType($type), $options, $alias);

        return $this;
    }

    /**
     * @throws DuplicateFilterException
     * @throws TypeNotAFilterException
     *
     * @phpstan-param class-string<FilterType>|null $type
     * @phpstan-param array<string, mixed> $options
     */
    public function addFilter(string $identifier, ?string $type = null, array $options = []): self
    {
        $type ??= DefaultFilterType::class;

        if (\in_array($identifier, $this->filterNames, true)) {
            throw new DuplicateFilterException('Filter '.$identifier.' already exists in '.$this->name.' Grid Helper');
        }

        if (!is_a($type, FilterType::class, true)) {
            throw new TypeNotAFilterException($type);
        }

        if (AutocompleteFilterType::class === $type || AutocompleteTextFilterType::class === $type) {
            $options['grid_field'] = $identifier;
        }

        if (AutocompleteTextFilterType::class === $type) {
            $options['text_property'] = $identifier;
        }

        $filter  = new Filter($identifier, $this->filterRegistry->getType($type), $options);
        $key     = $filter->getLabel();

        $this->filterNames[$key]    = $identifier;
        $this->filters[$identifier] = $filter;

        if ($filter->isVisible()) {
            $this->defaultShowFilters[] = $filter;
            $this->filter->setHasDefaultShowFilter(true);
        }

        return $this;
    }

    /**
     * @return Column[]
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    public function getColumnForAlias(string $alias): ?Column
    {
        foreach ($this->columns as $column) {
            if ($column->getAlias() === $alias) {
                return $column;
            }
        }

        return null;
    }

    public function getHideableColumns(): array
    {
        $res = [];
        foreach ($this->getColumns() as $column) {
            if ($column->getOption('isHideable') === true) {
                $res[$column->getLabel()] = $column->getField();
            }
        }

        return $res;
    }

    public function getNotHideableColumns(): array
    {
        $res = [];
        foreach ($this->getColumns() as $column) {
            if (!$column->getOption('isHideable')) {
                $res[$column->getLabel()] = $column->getField();
            }
        }

        return $res;
    }

    public function getQueryBuilder(): QueryBuilder
    {
        return $this->queryBuilder;
    }

    public function getDefaultPage(): int
    {
        return $this->defaultPage;
    }

    public function setDefaultPage(int $defaultPage): void
    {
        $this->defaultPage = $defaultPage;
    }

    public function getPerPage(): int
    {
        return $this->options['defaultPerPage'];
    }

    public function getFilter(): FilterEntity
    {
        $this->filter->setFields($this->filterNames);

        if ($this->filter->getRows()->count() > 0) {
            return $this->filter;
        }

        $fields = $this->filter->getFields();
        if (\count($this->defaultShowFilters) > 0) {
            foreach ($this->defaultShowFilters as $defaultShowFilter) {
                $row = new FilterRow();
                $row->setField($defaultShowFilter->getField());

                /** @var DefaultFilterDataStruct $defaultData */
                $defaultData = $defaultShowFilter->getOption('default_data');

                if (null !== $defaultData) {
                    $row->setMetaData($defaultData->serialize());
                } else {
                    $row->setOperator(array_key_first($defaultShowFilter->getOption('operators', [])));
                }

                $this->filter->addRow($row);
            }
        } else {
            $row = new FilterRow();
            if (\count($fields) > 0) {
                $row->setField($fields[array_key_first($fields)]);
            }
            $this->filter->addRow($row);
        }

        if (\count($fields) > 0 && 1 === $this->filter->getRows()->count() && !$this->filter->getRows()->first(
            )->getField()) {
            $this->filter->getRows()->first()->setField($fields[array_key_first($fields)]);
        }

        return $this->filter;
    }

    /**
     * @return array<string, Filter>
     */
    public function getFilters(): array
    {
        return $this->filters;
    }

    public function getTitle(): string
    {
        return $this->options['title'];
    }

    public function getListRow(): string
    {
        return $this->options['listRow'];
    }

    public function getPaginationTemplate(): string
    {
        return $this->options['paginationTemplate'];
    }

    public function getListHeaderTemplate(): string
    {
        return $this->options['listHeaderTemplate'];
    }

    public function getFilterView(): string
    {
        return $this->options['filter_view'];
    }

    public function getListView(): string
    {
        return $this->options['list_view'];
    }

    public function getCreateRoute(): ?string
    {
        return $this->options['createRoute'];
    }

    public function getCreateLabel(): ?string
    {
        return $this->options['createLabel'];
    }

    public function getPerPageOptions(): array
    {
        return $this->options['perPageOptions'];
    }

    public function getPageParameterName()
    {
        return $this->options['pageParameterName'];
    }

    public function getPerPageParameterName()
    {
        return $this->options['perPageParameterName'];
    }

    public function getWrapQueries(): bool
    {
        return $this->options['wrapQueries'];
    }

    public function getDistinctQuery(): bool
    {
        return $this->options['distinctQuery'];
    }

    public function getAlias(): string
    {
        return $this->alias;
    }

    public function getFilterTypeForField(string $field): Filter
    {
        return $this->filters[$field];
    }

    public function getAllowSaveFilter(): bool
    {
        return $this->options['allow_save_filter'];
    }

    public function getCurrentUserIdentifier(): string
    {
        return $this->options['current_user_identifier'];
    }

    public function getUserSettingsEnabled(): bool
    {
        return $this->options['user_settings_enabled'];
    }
}
