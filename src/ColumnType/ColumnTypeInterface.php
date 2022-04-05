<?php

namespace Unlooped\GridBundle\ColumnType;

use Symfony\Component\OptionsResolver\OptionsResolver;

interface ColumnTypeInterface
{
    public function configureOptions(OptionsResolver $resolver): void;

    public function getValue(string $field, object $object, array $options = []);

    public function hasAggregates(array $options): bool;
}
