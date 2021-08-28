<?php

namespace Unlooped\GridBundle\ColumnType;

use Symfony\Component\Intl\Currencies;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CurrencyColumn extends AbstractColumnType
{
    protected $template = '@UnloopedGrid/column_types/currency.html.twig';

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'currency' => 'EUR',
        ]);

        $resolver->setAllowedTypes('currency', ['string']);
        $resolver->setAllowedValues('currency', Currencies::getCurrencyCodes());
    }
}
