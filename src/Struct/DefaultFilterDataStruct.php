<?php

namespace Unlooped\GridBundle\Struct;

use Unlooped\GridBundle\FilterType\AbstractFilterType;

class DefaultFilterDataStruct
{
    public ?string $operator = AbstractFilterType::EXPR_CONTAINS;

    public $value;

    /**
     * @var array<string, mixed>
     */
    public array $metaData = [];

    public function serialize(): array
    {
        $metadata             = $this->metaData;
        $metadata['operator'] = $this->operator;
        $metadata['value']    = $this->value;

        return $metadata;
    }
}
