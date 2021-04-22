<?php

namespace Unlooped\GridBundle\Struct;

use Unlooped\GridBundle\FilterType\AbstractFilterType;

class DefaultFilterDataStruct
{
    /**
     * @var string|null
     */
    public $operator = AbstractFilterType::EXPR_CONTAINS;

    /**
     * @var mixed|mixed[]
     */
    public $value;

    /**
     * @var array<string, mixed>
     */
    public $metaData = [];

    public function serialize(): array
    {
        $metadata             = $this->metaData;
        $metadata['operator'] = $this->operator;
        $metadata['value']    = $this->value;

        return $metadata;
    }
}
