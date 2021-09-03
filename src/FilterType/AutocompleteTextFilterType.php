<?php

namespace Unlooped\GridBundle\FilterType;

use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\RouterInterface;

class AutocompleteTextFilterType extends TextFilterType
{
    private RouterInterface $router;

    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'route'                => 'unlooped_grid_autocomplete',
            'minimum_input_length' => 2,
            'grid'                 => null,
            'grid_field'           => null,
            'text_property'        => null,
            'property'             => null,
            'filter_callback'      => null,
        ]);

        $resolver->setRequired('entity');
        $resolver->setRequired('grid');
        $resolver->setRequired('grid_field');

        $resolver->setAllowedTypes('route', 'string');
        $resolver->setAllowedTypes('entity', 'string');
        $resolver->setAllowedTypes('grid', ['string', 'null']);
        $resolver->setAllowedTypes('grid_field', ['string', 'null']);
        $resolver->setAllowedTypes('minimum_input_length', 'int');
        $resolver->setAllowedTypes('text_property', ['string', 'null']);
        $resolver->setAllowedTypes('property', ['string', 'array', 'null']);
        $resolver->setAllowedTypes('filter_callback', ['null', 'callable']);
    }

    public function buildForm($builder, array $options = [], $data = null): void
    {
        $builder
            ->add('value', HiddenType::class, [
                'attr' => [
                    'data-ajax--url' => $this->router->generate($options['route'], [
                        'grid'       => $options['grid'],
                        'field'      => $options['grid_field'],
                        'page_limit' => 10,
                    ]),
                    'data-ajax--cache'          => true,
                    'data-ajax--cache-timeout'  => 60000,
                    'data-ajax--delay'          => 250,
                    'data-ajax--data-type'      => 'json',
                    'data-language'             => 'de',
                    'data-minimum-input-length' => $options['minimum_input_length'],
                    'data-placeholder'          => '',
                    'data-page-limit'           => 10,
                    'data-scroll'               => 'false',
                    'data-autostart'            => 'true',
                    'data-allow-clear'          => 'true',
                    'data-property'             => $options['property'],
                    'class'                     => 'select2text form-control',
                ],
                'block_prefix' => 'gridbundle_autocomplete',
            ])
        ;
    }
}
