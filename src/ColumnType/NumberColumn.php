<?php

namespace Unlooped\GridBundle\ColumnType;

use Symfony\Component\OptionsResolver\OptionsResolver;
use Unlooped\GridBundle\Struct\AggregateResultStruct;

class NumberColumn extends AbstractColumnType
{
    protected $template = '@UnloopedGrid/column_types/number.html.twig';

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'nullAsZero'     => false,
            'attr'           => ['class' => 'text-right'],
            'formatOptions'  => [],
            'style'          => 'decimal',
            'aggregates'     => [],
            'show_aggregate' => null,
        ]);

        $resolver->setAllowedTypes('aggregates', ['array']);
        $resolver->setAllowedTypes('show_aggregate', ['null', 'string']);

        $resolver->setAllowedValues('show_aggregate', [null, 'sum', 'avg', 'min', 'max', 'count', 'count_distinct', 'group_concat', 'std']);
    }

    public function getAggregateAlias(string $aggregate, string $field): string
    {
        return $aggregate . '_' . str_replace('.', '_', $field);
    }

    public function hasAggregates(array $options): bool
    {
        return $options['show_aggregate'] !== null || count($options['aggregates']) > 0;
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
