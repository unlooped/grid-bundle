<?php

namespace Unlooped\GridBundle\Struct;

use Doctrine\ORM\Mapping\ClassMetadata;

class FieldMetaDataStruct {

    /** @var string */
    public $alias;
    /** @var array */
    public $fieldData;

    public static function create(string $alias, ?array $fieldData = null): self
    {
        $s = new self();
        $s->alias = $alias;
        $s->fieldData = $fieldData;

        return $s;
    }

    public function __toString(): string
    {
        return (string)$this->alias;
    }

}
