<?php

namespace Unlooped\GridBundle\Filter;

use ArrayAccess;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Unlooped\GridBundle\Entity\FilterRow;
use Unlooped\GridBundle\FilterType\FilterType;

final class Filter implements ArrayAccess
{
    private string $field;

    private FilterType $type;

    private array $options;

    public function __construct(string $field, FilterType $type, array $options = [])
    {
        $this->field   = $field;
        $this->type    = $type;

        $resolver = new OptionsResolver();
        $this->type->configureOptions($resolver);

        $this->options = $resolver->resolve($options);
    }

    public function getField(): string
    {
        return $this->field;
    }

    public function getType(): FilterType
    {
        return $this->type;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function getOption(string $key, $default = null)
    {
        return $this->options[$key] ?? $default;
    }

    public function getLabel(): string
    {
        return $this->getOption('label', $this->field);
    }

    public function isVisible(): bool
    {
        return $this->getOption('show_filter', false);
    }

    /**
     * @param FormBuilderInterface|FormInterface $builder
     *
     * @internal
     */
    public function preSubmitFormData($builder): void
    {
        $this->type->preSubmitFormData($builder, $this->getOptions());
    }

    public function handleFilter(QueryBuilder $qb, FilterRow $filterRow): void
    {
        $this->type->handleFilter($qb, $filterRow, $this->options);
    }

    public function offsetExists($offset): bool
    {
        return isset($this->options[$offset]);
    }

    public function offsetGet($offset)
    {
        return $this->getOption($offset);
    }

    public function offsetSet($offset, $value): void
    {
        $this->options[$offset] = $value;
    }

    public function offsetUnset($offset): void
    {
        unset($this->options[$offset]);
    }
}
