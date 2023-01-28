<?php

namespace Unlooped\GridBundle\DependencyInjection\Compiler;

use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Unlooped\GridBundle\Column\Registry\ColumnRegistry;
use Unlooped\GridBundle\DependencyInjection\Configuration;

final class GridColumnPass implements CompilerPassInterface
{
    public const COLUMN_TAG = 'unlooped_grid.column';

    public function process(ContainerBuilder $container): void
    {
        if (!$container->has(ColumnRegistry::class)) {
            return;
        }

        $configs       = $container->getExtensionConfig('unlooped_grid');
        $configuration = new Configuration();
        $config        = $this->processConfiguration($configuration, $configs);

        $definition = $container->findDefinition(ColumnRegistry::class);

        $taggedServices = $container->findTaggedServiceIds(self::COLUMN_TAG);
        foreach ($taggedServices as $id => $tags) {
            $container->getDefinition($id)
                ->addMethodCall('setBaseTemplatePath', [$config['template']])
            ;

            $definition->addMethodCall('addType', [$id, new Reference($id)]);
        }
    }

    private function processConfiguration(ConfigurationInterface $configuration, array $configs): array
    {
        return (new Processor())->processConfiguration($configuration, $configs);
    }
}
