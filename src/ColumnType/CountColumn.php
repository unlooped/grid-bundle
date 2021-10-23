<?php

namespace Unlooped\GridBundle\ColumnType;

class CountColumn extends NumberColumn
{
    public function getValue(string $field, object $object, array $options = [])
    {
        $value = parent::getValue($field, $object, $options);

        return is_countable($value) ? count($value) : null;
    }
}
