<?php

namespace Unlooped\GridBundle\FilterType;

use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MoneyRangeFilterType extends NumberRangeFilterType {

    public function buildForm($builder, array $options = [], $data = null): void
    {
        $builder
            ->remove('value')
            ->add('_number_from', MoneyType::class, [
                'mapped'   => false,
                'required' => false,
                'currency' => $options['currency'],
            ])
            ->add('_number_to', MoneyType::class, [
                'mapped'   => false,
                'required' => false,
                'currency' => $options['currency'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'currency' => 'EUR',
        ]);

        $resolver->setAllowedTypes('currency', ['string']);
    }


}
