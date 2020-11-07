<?php

namespace Unlooped\GridBundle\ColumnType;

use Exception;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\PropertyAccess;

abstract class AbstractColumnType implements ColumnTypeInterface
{
    protected $template = '@UnloopedGrid/column_types/text.html.twig';

    protected $options;
    protected $field;
    protected $propertyAccessor;
    protected $alias;

    /**
     * Text constructor.
     */
    public function __construct(string $field, array $options = [], string $alias = null)
    {
        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);
        $this->options = $resolver->resolve($options);

        $this->propertyAccessor = PropertyAccess::createPropertyAccessorBuilder()->disableExceptionOnInvalidPropertyPath()->getPropertyAccessor();

        $this->field = $field;
        $this->alias = $alias;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'label'      => null,
            'isSortable' => true,
            'isMapped'   => true,
            'attr'       => [],
            'template'   => $this->template,
            'meta'       => [],
        ]);

        $resolver->setAllowedTypes('label', ['null', 'string']);
        $resolver->setAllowedTypes('isSortable', 'bool');
        $resolver->setAllowedTypes('attr', 'array');
        $resolver->setAllowedTypes('template', ['null', 'string']);
        $resolver->setAllowedTypes('meta', 'array');
    }

    public function getValue($object)
    {
        if ($this->options['isMapped']) {
            try {
                return $this->propertyAccessor->getValue($object, $this->field);
            } catch (Exception $e) {
                return null;
            }
        }

        return $this->options['label'] ?? $this->field;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function setOptions(array $options): void
    {
        $this->options = $options;
    }

    public function getTemplate(): string
    {
        return $this->template;
    }

    public function getField(): string
    {
        return $this->field;
    }

    public function getAlias(): string
    {
        return $this->alias;
    }

    public function getFieldWithAlias(): string
    {
        return $this->alias.'.'.$this->field;
    }

    public function isSortable(): bool
    {
        return $this->options['isSortable'];
    }

    public function getLabel(): string
    {
        return $this->options['label'] ?? $this->field;
    }

    public function getAttr()
    {
        return $this->options['attr'];
    }
}
