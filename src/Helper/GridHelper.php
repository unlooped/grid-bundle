<?php

namespace Unlooped\GridBundle\Helper;

use Doctrine\ORM\QueryBuilder;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Unlooped\GridBundle\ColumnType\AbstractColumnType;
use Unlooped\GridBundle\ColumnType\TextColumn;
use Unlooped\GridBundle\Entity\Filter;
use Unlooped\GridBundle\Entity\FilterRow;
use Unlooped\GridBundle\Exception\DuplicateColumnException;
use Unlooped\GridBundle\Exception\DuplicateFilterException;
use Unlooped\GridBundle\Exception\TypeNotAColumnException;
use Unlooped\GridBundle\Exception\TypeNotAFilterException;
use Unlooped\GridBundle\FilterType\FilterType;
use Unlooped\GridBundle\Struct\DefaultFilterDataStruct;

class GridHelper
{
    /** @var QueryBuilder */
    private $queryBuilder;
    /** @var string */
    private $name;
    /** @var int */
    private $defaultPage = 1;

    /** @var AbstractColumnType[]|array */
    private $columns     = [];
    private $columnNames = [];

    /** @var Filter|null */
    private $filter;
    private $filters = [];
    /** @var FilterType[] */
    private $defaultShowFilters = [];
    private $filterNames        = [];
    private $options;
    private $alias;

    public function __construct(QueryBuilder $queryBuilder, array $options = [], Filter $filter = null)
    {
        $this->queryBuilder = $queryBuilder;
        $this->alias        = $this->queryBuilder->getRootAliases()[0];
        $this->filter       = $filter;

        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);

        $this->options = $resolver->resolve($options);
    }

    public static function create(QueryBuilder $queryBuilder, array $options = [], Filter $filter = null): self
    {
        return new self($queryBuilder, $options, $filter);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'name'                    => '',
            'allow_duplicate_columns' => false,
            'listRow'                 => '@UnloopedGrid/list_row.html.twig',
            'paginationTemplate'      => '@UnloopedGrid/_pagination.html.twig',
            'listHeaderTemplate'      => '@UnloopedGrid/column_headers.html.twig',
            'title'                   => '',
            'createRoute'             => null,
            'createLabel'             => null,
            'defaultPerPage'          => 24,
            'perPageOptions'          => [24, 48, 72, 96, 120, 144, 168, 192],
            'pageParameterName'       => 'page',
            'perPageParameterName'    => 'perPage',
            'wrapQueries'             => false,
            'distinctQuery'           => false,
            'allow_save_filter'       => true,
        ]);

        $resolver->setAllowedTypes('name', 'string');
        $resolver->setAllowedTypes('allow_duplicate_columns', 'bool');
        $resolver->setAllowedTypes('listRow', 'string');
        $resolver->setAllowedTypes('paginationTemplate', 'string');
        $resolver->setAllowedTypes('listHeaderTemplate', 'string');
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

        $resolver->setRequired(['title', 'listRow']);
    }

    /**
     * @throws DuplicateColumnException
     * @throws TypeNotAColumnException
     */
    public function addColumn(string $identifier, string $type = TextColumn::class, array $options = []): self
    {
        if (!$this->options['allow_duplicate_columns'] && \in_array($identifier, $this->columnNames, true)) {
            throw new DuplicateColumnException('Column '.$identifier.' already exists in '.$this->name.' Grid Helper');
        }

        if (!is_a($type, AbstractColumnType::class, true)) {
            throw new TypeNotAColumnException($type);
        }

        $alias = RelationsHelper::getAliasForEntityAndField($this->getQueryBuilder(), $this->filter->getEntity(), $identifier);

        $this->columnNames[] = $identifier;
        $this->columns[]     = new $type($identifier, $options, $alias);

        return $this;
    }

    /**
     * @throws DuplicateFilterException
     * @throws TypeNotAFilterException
     */
    public function addFilter(string $identifier, ?string $type = FilterType::class, array $options = []): self
    {
        if (\in_array($identifier, $this->filterNames, true)) {
            throw new DuplicateFilterException('Filter '.$identifier.' already exists in '.$this->name.' Grid Helper');
        }

        if (!is_a($type, FilterType::class, true)) {
            throw new TypeNotAFilterException($type);
        }

        /** @var FilterType $filterType */
        $filterType                 = new $type($identifier, $options);
        $key                        = $filterType->getOptions()['label'] ?? $identifier;
        $this->filterNames[$key]    = $identifier;
        $this->filters[$identifier] = $filterType;
        if (true === $filterType->getOptions()['show_filter']) {
            $this->defaultShowFilters[] = $filterType;
            $this->filter->setHasDefaultShowFilter(true);
        }

        return $this;
    }

    public function getColumns(): array
    {
        return $this->columns;
    }

    public function getColumnForAlias(string $alias): ?AbstractColumnType
    {
        foreach ($this->columns as $column) {
            if ($column->getAlias() === $alias) {
                return $column;
            }
        }

        return null;
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

    public function getFilter(): Filter
    {
        $this->filter->setFields($this->filterNames);

        if ($this->filter->getRows()->count() > 0) {
            return $this->filter;
        }

        $fields = $this->filter->getFields();
        if (\count($this->defaultShowFilters) > 0) {
            foreach ($this->defaultShowFilters as $defaultShowFilter) {
                $fRow = new FilterRow();
                $fRow->setField($defaultShowFilter->getField());
                /** @var DefaultFilterDataStruct $defaultData */
                if ($defaultData = $defaultShowFilter->getOptions()['default_data']) {
                    $fRow->setOperator($defaultData->operator);
                    $fRow->setValue($defaultData->value);
                    $fRow->setMetaData($defaultData->metaData);
                } else {
                    $fRow->setOperator(array_key_first($defaultShowFilter::getAvailableOperators()));
                }

                $this->filter->addRow($fRow);
            }
        } else {
            $fRow = new FilterRow();
            if (\count($fields) > 0) {
                $fRow->setField($fields[array_key_first($fields)]);
            }
            $this->filter->addRow($fRow);
        }

        if (\count($fields) > 0 && 1 === $this->filter->getRows()->count() && !$this->filter->getRows()->first()->getField()) {
            $this->filter->getRows()->first()->setField($fields[array_key_first($fields)]);
        }

        return $this->filter;
    }

    /**
     * @return array|FilterType[]
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

    public function getFilterTypeForField(string $field): FilterType
    {
        return $this->filters[$field];
    }

    public function getAllowSaveFilter(): bool
    {
        return $this->options['allow_save_filter'];
    }
}
