<?php

namespace Unlooped\GridBundle\FilterType;

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Unlooped\GridBundle\Entity\FilterRow;
use Unlooped\Helper\StringHelper;

class NullFilterType extends AbstractFilterType {

    public static function getAvailableOperators(): array
    {
        return [
            self::EXPR_EQ  => self::EXPR_EQ,
        ];
    }

    public function buildForm($builder, array $options = [], $data = null): void
    {
        $builder
            ->add('value', ChoiceType::class, [
                'required' => false,
                'choices' => ['Is Set' => 'is_set', 'Is Not Set' => 'is_not_set'],
            ])
        ;
    }

    public function getExpressionOperator(FilterRow $filterRow): string
    {
        $value = $filterRow->getValue();

        if ($value === 'is_set') {
            return StringHelper::camelize(self::IEXPR_IS_NOT_NULL)->toString();
        }

        return StringHelper::camelize(self::IEXPR_IS_NULL)->toString();
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
