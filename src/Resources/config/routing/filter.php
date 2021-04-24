<?php

use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use Unlooped\GridBundle\Action\AutocompleteAction;

return static function (RoutingConfigurator $routes): void {
    $routes
        ->add('unlooped_grid_autocomplete', '/autocomplete')
        ->controller(AutocompleteAction::class)
    ;
};
