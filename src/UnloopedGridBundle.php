<?php

namespace Unlooped\GridBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Unlooped\GridBundle\DependencyInjection\Compiler\GridColumnPass;
use Unlooped\GridBundle\DependencyInjection\Compiler\GridFilterPass;

class UnloopedGridBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new GridColumnPass());
        $container->addCompilerPass(new GridFilterPass());
    }
}
