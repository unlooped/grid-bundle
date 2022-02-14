<?php

namespace Unlooped\GridBundle\ColumnType;

use Symfony\Component\OptionsResolver\OptionsResolver;

class NumberColumn extends AbstractColumnType
{
    protected $template = '@UnloopedGrid/column_types/number.html.twig';

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'nullAsZero'    => false,
            'attr'          => ['class' => 'text-right'],
            'formatOptions' => [],
            'style'         => 'decimal',
        ]);
    }
}
