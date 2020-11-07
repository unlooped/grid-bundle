<?php

namespace Unlooped\GridBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('unlooped_grid');

        $treeBuilder->getRootNode()
            ->children()
                ->booleanNode('save_filter')->defaultFalse()->end()
                ->booleanNode('use_route_in_filter_reference')->defaultTrue()->info('If set to true the route is used to Identify filters to be loaded')->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
