<?php

namespace Unlooped\GridBundle\FilterType;

use Symfony\Component\Form\Extension\Core\Type\PercentType;

class PercentFilterType extends AbstractFilterType {

    public static function getAvailableOperators(): array
    {
        return [
            self::EXPR_EQ  => self::EXPR_EQ,
            self::EXPR_LT  => self::EXPR_LT,
            self::EXPR_LTE => self::EXPR_LTE,
            self::EXPR_GT  => self::EXPR_GT,
            self::EXPR_GTE => self::EXPR_GTE,
        ];
    }

    public function buildForm($builder, array $options = [], $data = null): void
    {
        $builder
            ->add('value', PercentType::class, ['required' => false])
        ;
    }

}
