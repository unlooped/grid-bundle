<?php

namespace Unlooped\GridBundle\ColumnType;

use Symfony\Component\OptionsResolver\OptionsResolver;

class BadgeColumn extends AbstractColumnType
{
    protected $template = '@UnloopedGrid/column_types/badge.html.twig';

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults(['classPrefix' => null]);
        $resolver->setAllowedTypes('classPrefix', ['null', 'string']);
    }
}
