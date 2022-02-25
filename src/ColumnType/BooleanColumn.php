<?php

namespace Unlooped\GridBundle\ColumnType;

use Symfony\Component\OptionsResolver\OptionsResolver;

class BooleanColumn extends AbstractColumnType
{
    protected $template = '@UnloopedGrid/column_types/boolean.html.twig';

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'nullAsFalse' => false,
        ]);

        $resolver->setAllowedTypes('nullAsFalse', 'bool');
    }


}
