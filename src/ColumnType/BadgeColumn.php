<?php

namespace Unlooped\GridBundle\ColumnType;

use Symfony\Component\OptionsResolver\OptionsResolver;

class BadgeColumn extends AbstractColumnType
{
    protected string $template = '@UnloopedGrid/column_types/badge.html.twig';

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'classPrefix' => null,
            'attr'        => ['class' => 'text-center'],
        ]);
        $resolver->setAllowedTypes('classPrefix', ['null', 'string']);
    }
}
