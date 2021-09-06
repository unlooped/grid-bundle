<?php

namespace Unlooped\GridBundle\FilterType;

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\PropertyPath;

class ChoiceFilterType extends AbstractFilterType
{
    protected $template = '@UnloopedGrid/filter_types/choice.html.twig';

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);
        $resolver->setDefaults([
            'widget'            => 'select',
            'choices'           => [],
            'preferred_choices' => [],
            'expanded'          => false,
            'multiple'          => false,
            'use_select2'       => false,
        ]);

        $resolver->setAllowedValues('widget', ['select']);
        $resolver->setAllowedTypes('choices', ['array']);
        $resolver->setAllowedTypes('preferred_choices', ['array', \Traversable::class, 'callable', 'string', PropertyPath::class]);
        $resolver->setAllowedTypes('expanded', ['bool']);
        $resolver->setAllowedTypes('multiple', ['bool']);
        $resolver->setAllowedTypes('use_select2', ['bool']);
    }

    public function buildForm($builder, array $options = [], $data = null): void
    {
        $builder
            ->add('value', ChoiceType::class, [
                'required'           => false,
                'translation_domain' => 'unlooped_grid',
                'choices'            => $options['choices'],
                'expanded'           => $options['expanded'],
                'multiple'           => $options['multiple'],
                'attr'               => [
                    'class' => ($options['use_select2'] ? 'initSelect2' : 'custom-select'),
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
