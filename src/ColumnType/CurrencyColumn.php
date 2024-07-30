<?php

namespace Unlooped\GridBundle\ColumnType;

use Symfony\Component\Intl\Currencies;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CurrencyColumn extends NumberColumn
{
    protected string $template = '@UnloopedGrid/column_types/currency.html.twig';

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'currency'      => 'EUR',
            'currency_path' => null,
            'divider'       => 0,
            'formatOptions' => [
                'fraction_digit' => 2,
            ],
        ]);

        $resolver->setAllowedTypes('currency', ['string', 'null']);
        $resolver->setAllowedTypes('currency_path', ['string', 'null']);

        $resolver->setAllowedValues('currency', Currencies::getCurrencyCodes());
    }

    public function getValue(string $field, object $object, array $options = [])
    {
        $value = parent::getValue($field, $object, $options);

        if ($options['divider'] > 0) {
            $value /= $options['divider'];
        }

        return $value;
    }

    public function getCurrency(object $object, array $options)
    {
        if ($options['currency_path']) {
            return $this->propertyAccessor->getValue($object, $options['currency_path']);
        }

        return $options['currency'];
    }
}
