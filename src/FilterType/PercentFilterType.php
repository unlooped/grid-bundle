<?php

namespace Unlooped\GridBundle\FilterType;

use Symfony\Component\Form\Extension\Core\Type\PercentType;

class PercentFilterType extends AbstractFilterType
{
    public static function getAvailableOperators(): array
    {
        return [
            static::EXPR_EQ  => static::EXPR_EQ,
            static::EXPR_LT  => static::EXPR_LT,
            static::EXPR_LTE => static::EXPR_LTE,
            static::EXPR_GT  => static::EXPR_GT,
            static::EXPR_GTE => static::EXPR_GTE,
        ];
    }

    public function buildForm($builder, array $options = [], $data = null): void
    {
        $builder
            ->add('value', PercentType::class, ['required' => false])
        ;
    }
}
