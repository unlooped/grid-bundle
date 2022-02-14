<?php

namespace Unlooped\GridBundle\FilterType;

class TextFilterType extends AbstractFilterType
{
    protected static function getAvailableOperators(): array
    {
        return [
            static::EXPR_CONTAINS     => static::EXPR_CONTAINS,
            static::EXPR_NOT_CONTAINS => static::EXPR_NOT_CONTAINS,
            static::EXPR_BEGINS_WITH  => static::EXPR_BEGINS_WITH,
            static::EXPR_ENDS_WITH    => static::EXPR_ENDS_WITH,
            static::EXPR_EQ           => static::EXPR_EQ,
            static::EXPR_NEQ          => static::EXPR_NEQ,
            static::EXPR_IS_EMPTY     => static::EXPR_IS_EMPTY,
            static::EXPR_IS_NOT_EMPTY => static::EXPR_IS_NOT_EMPTY,
        ];
    }
}
