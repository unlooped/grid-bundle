<?php

namespace Unlooped\GridBundle\DependencyInjection;

use Exception;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;
use Unlooped\GridBundle\Service\GridService;

class UnloopedGridExtension extends ConfigurableExtension {

    /**
     * Loads a specific configuration.
     *
     * @param array $config
     * @param ContainerBuilder $container
     * @throws Exception
     */
    public function loadInternal(array $config, ContainerBuilder $container): void
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yaml');


        $gsDef = $container->getDefinition(GridService::class);
        $gsDef->replaceArgument(5, $config['save_filter']);
        $gsDef->replaceArgument(6, $config['use_route_in_filter_reference']);
    }
}
