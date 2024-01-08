<?php

namespace Unlooped\GridBundle\FilterType;

use Symfony\Component\Form\Extension\Core\Type\CountryType;

class CountryFilterType extends ChoiceFilterType
{
    public function buildForm($builder, array $options = [], $data = null): void
    {
        $builder
            ->add('value', CountryType::class, [
                'required'           => false,
                'translation_domain' => 'unlooped_grid',
                'attr'               => [
                    'class' => ($options['use_select2'] ? 'initSelect2' : 'custom-select'),
                ],
                'preferred_choices' => $options['preferred_choices'],
            ])
        ;
    }
}
