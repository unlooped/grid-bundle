<?php

namespace Unlooped\GridBundle\ColumnType;

use Symfony\Component\OptionsResolver\OptionsResolver;
use Unlooped\GridBundle\Struct\AggregateResultStruct;

class NumberColumn extends AbstractColumnType
{
    protected string $template = '@UnloopedGrid/column_types/number.html.twig';

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'nullAsZero'         => false,
            'attr'               => ['class' => 'text-right'],
            'formatOptions'      => [],
            'style'              => 'decimal',
            'aggregates'         => [],
            'show_aggregate'     => null,
            'aggregate_callback' => null,
        ]);

        $resolver->setAllowedTypes('aggregates', ['array']);
        $resolver->setAllowedTypes('show_aggregate', ['null', 'string']);
        $resolver->setAllowedTypes('aggregate_callback', ['null', 'callable']);

        $resolver->setAllowedValues('show_aggregate', [null, 'sum', 'avg', 'min', 'max', 'count', 'count_distinct', 'group_concat', 'std', 'callback']);
    }

    public function getAggregateAlias(string $aggregate, string $field): string
    {
        return $aggregate.'_'.str_replace('.', '_', $field);
    }

    public function hasAggregates(array $options): bool
    {
        return null !== $options['show_aggregate'] || \count($options['aggregates']) > 0;
    }

    public function getValue(string $field, object $object, array $options = [])
    {
        if ($object instanceof AggregateResultStruct) {
            $alias = $this->getAggregateAlias($options['show_aggregate'], $field);

            return $object->getAggregateResultFor($alias);
        }

        return parent::getValue($field, $object, $options);
    }
}
