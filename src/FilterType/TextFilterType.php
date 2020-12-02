<?php

namespace Unlooped\GridBundle\FilterType;

class TextFilterType extends AbstractFilterType
{
    protected static function getAvailableOperators(): array
    {
        return [
            self::EXPR_CONTAINS     => self::EXPR_CONTAINS,
            self::EXPR_NOT_CONTAINS => self::EXPR_NOT_CONTAINS,
            self::EXPR_BEGINS_WITH  => self::EXPR_BEGINS_WITH,
            self::EXPR_ENDS_WITH    => self::EXPR_ENDS_WITH,
            self::EXPR_EQ           => self::EXPR_EQ,
            self::EXPR_NEQ          => self::EXPR_NEQ,
            self::EXPR_IS_EMPTY     => self::EXPR_IS_EMPTY,
            self::EXPR_IS_NOT_EMPTY => self::EXPR_IS_NOT_EMPTY,
        ];
    }
}
