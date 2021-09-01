<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Unlooped\GridBundle\DependencyInjection\Compiler\GridFilterPass;
use Unlooped\GridBundle\Filter\Registry\FilterRegistry;
use Unlooped\GridBundle\FilterType\AutocompleteFilterType;
use Unlooped\GridBundle\FilterType\AutocompleteTextFilterType;
use Unlooped\GridBundle\FilterType\BooleanFilterType;
use Unlooped\GridBundle\FilterType\ChoiceFilterType;
use Unlooped\GridBundle\FilterType\DateFilterType;
use Unlooped\GridBundle\FilterType\DateRangeFilterType;
use Unlooped\GridBundle\FilterType\EntityFilterType;
use Unlooped\GridBundle\FilterType\FilterType;
use Unlooped\GridBundle\FilterType\NumberRangeFilterType;
use Unlooped\GridBundle\FilterType\TextFilterType;

return static function (ContainerConfigurator $container): void {
    $container->services()
        ->defaults()
            ->autowire()

        ->instanceof(FilterType::class)
            ->tag(GridFilterPass::FILTER_TAG)

        ->set(FilterRegistry::class)

        ->set(AutocompleteFilterType::class)
            ->args([
                ref('doctrine'),
                ref('property_accessor'),
            ])
        ->set(BooleanFilterType::class)
        ->set(ChoiceFilterType::class)
        ->set(DateFilterType::class)
        ->set(DateRangeFilterType::class)
        ->set(EntityFilterType::class)
        ->set(NumberRangeFilterType::class)
        ->set(TextFilterType::class)
        ->set(AutocompleteTextFilterType::class)
            ->args([
                ref('router'),
            ])
    ;
};
