<?php

namespace Unlooped\GridBundle\FilterType;

use Symfony\Component\Form\Extension\Core\Type\PercentType;

class PercentRangeFilterType extends NumberRangeFilterType
{
    public function buildForm($builder, array $options = [], $data = null): void
    {
        $constraints = $this->getFormConstraints($builder, $options, $data);

        $builder
            ->remove('value')
            ->add('_number_from', PercentType::class, [
                'mapped'      => false,
                'required'    => false,
                'constraints' => $this->getFormConstraints($builder, $options, $data, '_number_from'),
            ])
            ->add('_number_to', PercentType::class, [
                'mapped'      => false,
                'required'    => false,
                'constraints' => $this->getFormConstraints($builder, $options, $data, '_number_to'),
            ])
        ;
    }
}
