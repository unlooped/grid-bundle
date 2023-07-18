<?php

namespace Unlooped\GridBundle\ColumnType;

use Symfony\Component\OptionsResolver\OptionsResolver;

class BooleanColumn extends AbstractColumnType
{
    protected string $template = '@UnloopedGrid/column_types/boolean.html.twig';

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'attr'        => ['class' => 'text-center'],
            'nullAsFalse' => false,
            'inverted'    => false,
        ]);

        $resolver->setAllowedTypes('nullAsFalse', 'bool');
        $resolver->setAllowedTypes('inverted', 'bool');
    }
}
