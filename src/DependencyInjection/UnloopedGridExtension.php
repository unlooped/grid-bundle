<?php

namespace Unlooped\GridBundle\DependencyInjection;

use Exception;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;
use Unlooped\GridBundle\Service\GridService;

class UnloopedGridExtension extends ConfigurableExtension implements PrependExtensionInterface
{
    /**
     * Loads a specific configuration.
     *
     * @throws Exception
     */
    public function loadInternal(array $config, ContainerBuilder $container): void
    {
        $yamlLoader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $yamlLoader->load('services.yaml');

        $phpLoader = new PhpFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $phpLoader->load('columns.php');
        $phpLoader->load('filter.php');

        $container->getDefinition(GridService::class)
            ->replaceArgument(10, $config['save_filter'])
        ;
    }

    public function prepend(ContainerBuilder $container): void
    {
        if ($container->hasExtension('twig')) {
            $container->prependExtensionConfig('twig', [
                'form_themes' => ['@UnloopedGrid/Form/fields.html.twig'],
            ]);
        }
    }
}
