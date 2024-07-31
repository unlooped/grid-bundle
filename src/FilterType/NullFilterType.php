<?php

namespace Unlooped\GridBundle\FilterType;

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Unlooped\GridBundle\Entity\FilterRow;

use function Symfony\Component\String\u;

class NullFilterType extends AbstractFilterType
{
    public static function getAvailableOperators(): array
    {
        return [
            static::EXPR_EQ  => static::EXPR_EQ,
        ];
    }

    public function buildForm($builder, array $options = [], $data = null): void
    {
        $builder
            ->add('value', ChoiceType::class, [
                'required' => false,
                'choices'  => ['Is Set' => 'is_set', 'Is Not Set' => 'is_not_set'],
            ])
        ;
    }

    public function getExpressionOperator(FilterRow $filterRow): string
    {
        $value = $filterRow->getValue();

        if ('is_set' === $value) {
            return u(static::IEXPR_IS_NOT_NULL)->camel()->toString();
        }

        return u(static::IEXPR_IS_NULL)->camel()->toString();
    }

    public function getExpressionValue(FilterRow $filterRow)
    {
        return null;
    }

    public function hasExpressionValue(FilterRow $filterRow): bool
    {
        return false;
    }
}
