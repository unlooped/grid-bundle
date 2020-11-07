<?php

namespace Unlooped\GridBundle\ColumnType;

use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @method bool isVisible(?UserInterface $user)
 */
interface ColumnTypeInterface
{
    public function getValue($object);

    public function getOptions(): array;

    public function getTemplate(): string;
}
