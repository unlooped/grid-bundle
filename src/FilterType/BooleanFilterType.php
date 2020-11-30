<?php

namespace Unlooped\GridBundle\FilterType;

use Symfony\Component\OptionsResolver\OptionsResolver;

class BooleanFilterType extends ChoiceFilterType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);
        $resolver->setDefaults([
            'widget'        => 'select',
            'choices'       => ['Any' => null, 'Yes' => '1', 'No' => '0'],
        ]);

        $resolver->setAllowedValues('widget', ['select']);
        $resolver->setAllowedTypes('choices', ['array']);
    }

    protected static function getAvailableOperators(): array
    {
        return [
            self::EXPR_EQ           => self::EXPR_EQ,
            self::EXPR_NEQ          => self::EXPR_NEQ,
        ];
    }
}
