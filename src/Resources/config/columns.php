<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Unlooped\GridBundle\Column\Registry\ColumnRegistry;
use Unlooped\GridBundle\ColumnType\ActionsColumn;
use Unlooped\GridBundle\ColumnType\BooleanColumn;
use Unlooped\GridBundle\ColumnType\ColumnTypeInterface;
use Unlooped\GridBundle\ColumnType\DateColumn;
use Unlooped\GridBundle\ColumnType\LocalizedDateColumn;
use Unlooped\GridBundle\ColumnType\TextColumn;
use Unlooped\GridBundle\DependencyInjection\Compiler\GridColumnPass;

return static function (ContainerConfigurator $container): void {
    $container->services()
        ->defaults()
            ->autowire()

        ->instanceof(ColumnTypeInterface::class)
            ->tag(GridColumnPass::COLUMN_TAG)

        ->set(ColumnRegistry::class)

        ->set(ActionsColumn::class)
        ->set(BooleanColumn::class)
        ->set(DateColumn::class)
        ->set(LocalizedDateColumn::class)
        ->set(TextColumn::class)
    ;
};
