<?php

namespace Unlooped\GridBundle\Column;

use ArrayAccess;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Unlooped\GridBundle\ColumnType\ColumnTypeInterface;

final class Column implements ArrayAccess
{
    private string $field;

    private ColumnTypeInterface $type;

    private array $options;

    private ?string $alias;

    public function __construct(string $field, ColumnTypeInterface $type, array $options = [], ?string $alias = null)
    {
        $this->field   = $field;
        $this->type    = $type;
        $this->alias   = $alias;

        $resolver = new OptionsResolver();
        $this->type->configureOptions($resolver);

        $this->options = $resolver->resolve($options);
    }

    public function getField(): string
    {
        return $this->field;
    }

    public function getType(): ColumnTypeInterface
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

    public function getValue(object $object)
    {
        return $this->type->getValue($this->field, $object, $this->options);
    }

    public function getAlias(): ?string
    {
        return $this->alias;
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

    public function hasAggregates(): bool
    {
        return $this->type->hasAggregates($this->options);
    }
}
