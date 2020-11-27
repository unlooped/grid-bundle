<?php

namespace Unlooped\GridBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Unlooped\GridBundle\Column\Registry\ColumnRegistry;

final class GridColumnPass implements CompilerPassInterface
{
    public const COLUMN_TAG = 'unlooped_grid.column';

    public function process(ContainerBuilder $container): void
    {
        if (!$container->has(ColumnRegistry::class)) {
            return;
        }

        $definition = $container->findDefinition(ColumnRegistry::class);

        $taggedServices = $container->findTaggedServiceIds(self::COLUMN_TAG);

        foreach ($taggedServices as $id => $tags) {
            $definition->addMethodCall('addType', [$id, new Reference($id)]);
        }
    }
}
