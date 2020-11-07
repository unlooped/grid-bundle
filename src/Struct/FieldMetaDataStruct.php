<?php

namespace Unlooped\GridBundle\Struct;

class FieldMetaDataStruct
{
    /** @var string */
    public $alias;
    /** @var array */
    public $fieldData;

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
