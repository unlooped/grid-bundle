<?php

namespace Unlooped\GridBundle\ColumnType;

use Symfony\Component\Intl\Countries;

class CountryColumn extends AbstractColumnType
{
    public function getValue(string $field, object $object, array $options = [])
    {
        $value = parent::getValue($field, $object, $options);

        if (!$value) {
            return '';
        }

        return Countries::getName($value);
    }
}
