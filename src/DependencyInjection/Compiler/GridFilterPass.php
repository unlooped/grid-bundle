<?php

namespace Unlooped\GridBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Unlooped\GridBundle\Filter\Registry\FilterRegistry;

final class GridFilterPass implements CompilerPassInterface
{
    public const FILTER_TAG = 'unlooped_grid.filter';

    public function process(ContainerBuilder $container): void
    {
        if (!$container->has(FilterRegistry::class)) {
            return;
        }

        $definition = $container->findDefinition(FilterRegistry::class);

        $taggedServices = $container->findTaggedServiceIds(self::FILTER_TAG);

        foreach ($taggedServices as $id => $tags) {
            $definition->addMethodCall('addType', [$id, new Reference($id)]);
        }
    }
}
