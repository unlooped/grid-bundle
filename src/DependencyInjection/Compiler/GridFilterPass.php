<?php

namespace Unlooped\GridBundle\DependencyInjection\Compiler;

use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Unlooped\GridBundle\DependencyInjection\Configuration;
use Unlooped\GridBundle\Filter\Registry\FilterRegistry;

final class GridFilterPass implements CompilerPassInterface
{
    public const FILTER_TAG = 'unlooped_grid.filter';

    public function process(ContainerBuilder $container): void
    {
        if (!$container->has(FilterRegistry::class)) {
            return;
        }

        $configs       = $container->getExtensionConfig('unlooped_grid');
        $configuration = new Configuration();
        $config        = $this->processConfiguration($configuration, $configs);

        $definition = $container->findDefinition(FilterRegistry::class);

        $taggedServices = $container->findTaggedServiceIds(self::FILTER_TAG);

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
