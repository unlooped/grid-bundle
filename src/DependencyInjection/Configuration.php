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
                ->scalarNode('template')->defaultValue('@UnloopedGrid')->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
