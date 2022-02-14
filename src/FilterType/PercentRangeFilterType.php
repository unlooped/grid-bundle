<?php

namespace Unlooped\GridBundle\FilterType;

use Symfony\Component\Form\Extension\Core\Type\PercentType;

class PercentRangeFilterType extends NumberRangeFilterType
{
    public function buildForm($builder, array $options = [], $data = null): void
    {
        $builder
            ->remove('value')
            ->add('_number_from', PercentType::class, [
                'mapped'   => false,
                'required' => false,
            ])
            ->add('_number_to', PercentType::class, [
                'mapped'   => false,
                'required' => false,
            ])
        ;
    }
}
