<?php

namespace Unlooped\GridBundle\FilterType;

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ChoiceFilterType extends AbstractFilterType
{
    protected $template = '@UnloopedGrid/filter_types/choice.html.twig';

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);
        $resolver->setDefaults([
            'widget'        => 'select',
            'choices'       => [],
        ]);

        $resolver->setAllowedValues('widget', ['select']);
        $resolver->setAllowedTypes('choices', ['array']);
    }

    public function buildForm($builder, array $options = [], $data = null): void
    {
        $builder
            ->remove('value')
            ->add('value', ChoiceType::class, [
                'required'           => false,
                'translation_domain' => 'unlooped_grid',
                'choices'            => $options['choices'],
                'attr'               => [
                    'class' => 'custom-select',
                ],
            ])
        ;
    }

    protected static function getAvailableOperators(): array
    {
        return [
            self::EXPR_EQ           => self::EXPR_EQ,
            self::EXPR_NEQ          => self::EXPR_NEQ,
            self::EXPR_IS_EMPTY     => self::EXPR_IS_EMPTY,
            self::EXPR_IS_NOT_EMPTY => self::EXPR_IS_NOT_EMPTY,
        ];
    }
}
