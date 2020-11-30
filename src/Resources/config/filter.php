<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Unlooped\GridBundle\DependencyInjection\Compiler\GridFilterPass;
use Unlooped\GridBundle\Filter\Registry\FilterRegistry;
use Unlooped\GridBundle\FilterType\FilterType;

return static function (ContainerConfigurator $container): void {
    $container->services()
        ->defaults()
            ->autowire()

        ->instanceof(FilterType::class)
            ->tag(GridFilterPass::FILTER_TAG)

        ->set(FilterRegistry::class)
    ;
};
