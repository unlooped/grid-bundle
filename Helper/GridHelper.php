<?php

namespace Unlooped\GridBundle\Helper;

use Doctrine\ORM\QueryBuilder;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Unlooped\GridBundle\ColumnType\AbstractColumnType;
use Unlooped\GridBundle\ColumnType\TextColumn;
use Unlooped\GridBundle\Entity\Filter;
use Unlooped\GridBundle\Exception\DuplicateColumnException;
use Unlooped\GridBundle\Exception\DuplicateFilterException;
use Unlooped\GridBundle\Exception\TypeNotAFilterException;
use Unlooped\GridBundle\FilterType\FilterType;

class GridHelper
{

    /** @var QueryBuilder */
    private $queryBuilder;
    /** @var string */
    private $name;
    /** @var int */
    private $defaultPage = 1;
    /** @var int */
    private $perPage = 24;

    /** @var array|AbstractColumnType[] */
    private $columns = [];
    private $columnNames = [];

    /** @var Filter|null */
    private $filter;
    private $filters = [];
    private $filterNames = [];
    private $options;
    private $alias;

    public static function create(QueryBuilder $queryBuilder, array $options = [], Filter $filter = null): GridHelper
    {
        return new self($queryBuilder, $options, $filter);
    }

    public function __construct(QueryBuilder $queryBuilder, array $options = [], Filter $filter = null)
    {
        $this->queryBuilder = $queryBuilder;
        $this->alias = $this->queryBuilder->getRootAliases()[0];
        $this->filter = $filter;

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
            'title'                   => '',
            'createRoute'             => null,
            'createLabel'             => null,
            'perPageOptions'          => [24, 48, 72, 96, 120, 144, 168, 192],
            'pageParameterName'       => 'page',
            'perPageParameterName'    => 'perPage',
            'wrapQueries'             => false,
            'distinctQuery'           => false,
        ]);

        $resolver->setAllowedTypes('name', 'string');
        $resolver->setAllowedTypes('allow_duplicate_columns', 'bool');
        $resolver->setAllowedTypes('listRow', 'string');
        $resolver->setAllowedTypes('title', 'string');
        $resolver->setAllowedTypes('createRoute', ['null', 'string']);
        $resolver->setAllowedTypes('createLabel', ['null', 'string']);
        $resolver->setAllowedTypes('perPageOptions', 'array');
        $resolver->setAllowedTypes('pageParameterName', 'string');
        $resolver->setAllowedTypes('perPageParameterName', 'string');
        $resolver->setAllowedTypes('wrapQueries', 'bool');
        $resolver->setAllowedTypes('distinctQuery', 'bool');

        $resolver->setRequired(['title', 'listRow']);
    }

    /**
     * @throws DuplicateColumnException
     */
    public function addColumn(string $identifier, string $type = TextColumn::class, array $options = []): self
    {
        if (!$this->options['allow_duplicate_columns'] && in_array($identifier, $this->columnNames, true)) {
            throw new DuplicateColumnException('Column ' . $identifier . ' already exists in ' . $this->name . ' Grid Helper');
        }

        $this->columnNames[] = $identifier;
        $this->columns[] = new $type($identifier, $options, $this->alias);

        return $this;
    }

    /**
     * @throws DuplicateFilterException
     * @throws TypeNotAFilterException
     */
    public function addFilter(string $identifier, ?string $type = FilterType::class, array $options = []): self
    {
        if (in_array($identifier, $this->filterNames, true)) {
            throw new DuplicateFilterException('Filter ' . $identifier . ' already exists in ' . $this->name . ' Grid Helper');
        }

        if (!is_a($type, FilterType::class, true)) {
            throw new TypeNotAFilterException($type);
        }

        $this->filterNames[] = $identifier;
        $this->filters[$identifier] = new $type($identifier, $options);

        return $this;
    }

    public function getColumns(): array
    {
        return $this->columns;
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
        return $this->perPage;
    }

    public function setPerPage(int $perPage): void
    {
        $this->perPage = $perPage;
    }

    public function getFilter(): Filter
    {
        $this->filter->setFields($this->filterNames);

        return $this->filter;
    }

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
}
