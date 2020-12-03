<?php

namespace Unlooped\GridBundle\FilterType;

use Symfony\Component\OptionsResolver\OptionsResolver;
use Unlooped\GridBundle\Form\EntityType;

final class EntityFilterType extends AbstractFilterType
{
    protected $template = '@UnloopedGrid/filter_types/entity.html.twig';

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver
            ->setDefaults([
                'class'     => '',
                'id_column' => 'id',
            ])
            ->setRequired('class')
            ->setAllowedTypes('class', 'string')
            ->setAllowedTypes('id_column', 'string')
        ;
    }

    /**
     * @param mixed|null $data
     */
    public function buildForm($builder, array $options = [], $data = null): void
    {
        $builder
            ->add('value', EntityType::class, [
                'required'            => false,
                'translation_domain'  => 'unlooped_grid',
                'class'               => $options['class'],
                'id_column'           => $options['id_column'],
            ])
        ;
    }

    protected static function getAvailableOperators(): array
    {
        return [
            self::EXPR_EQ   => self::EXPR_EQ,
            self::EXPR_NEQ  => self::EXPR_NEQ,
        ];
    }
}
