<?php

namespace Unlooped\GridBundle\ColumnType;

use Symfony\Component\OptionsResolver\OptionsResolver;

class ActionsColumn extends AbstractColumnType
{
    protected $template = '@UnloopedGrid/column_types/actions.html.twig';

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'actions'    => [],
            'isMapped'   => false,
            'isSortable' => false,
        ]);

        $resolver->setAllowedTypes('actions', ['array']);
    }
}
