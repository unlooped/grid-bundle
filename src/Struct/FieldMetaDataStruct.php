<?php

namespace Unlooped\GridBundle\Struct;

class FieldMetaDataStruct
{
    public ?string $alias;
    public ?array $fieldData;

    public function __toString(): string
    {
        return (string) $this->alias;
    }

    public static function create(string $alias, ?array $fieldData = null): self
    {
        $s            = new self();
        $s->alias     = $alias;
        $s->fieldData = $fieldData;

        return $s;
    }
}
