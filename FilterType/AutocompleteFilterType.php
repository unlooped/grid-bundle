<?php


namespace Unlooped\GridBundle\FilterType;


use App\Entity\Influencer;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Tetranz\Select2EntityBundle\Form\Type\Select2EntityType;

class AutocompleteFilterType extends FilterType
{
    public static function getAvailableOperators(): array
    {
        return [
            self::EXPR_EQ           => self::EXPR_EQ,
            self::EXPR_NEQ          => self::EXPR_NEQ,
        ];
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'route' => '',
            'entity' => '',
            'text_property' => null,
            'minimum_input_length' => 2,
        ]);

        $resolver->setRequired('route');
        $resolver->setRequired('entity');
        $resolver->setRequired('minimum_input_length');

        $resolver->setAllowedTypes('route', 'string');
        $resolver->setAllowedTypes('entity', 'string');
        $resolver->setAllowedTypes('minimum_input_length', 'int');
        $resolver->setAllowedTypes('text_property', ['string', 'null']);
    }


    public function buildForm($builder, array $options = [], $data = null): void
    {
        $builder
            ->remove('value')
            ->add('value', Select2EntityType::class, [
                'multiple' => false,
                'remote_route' => $this->getOptions()['route'],
                'class' => $this->getOptions()['entity'],
                'primary_key' => 'id',
                'minimum_input_length' => $this->getOptions()['entity'],
                'text_property' => $this->getOptions()['text_property'],
                'page_limit' => 10,
                'allow_clear' => true,
                'delay' => 250,
                'cache' => true,
                'cache_timeout' => 60000, // if 'cache' is true
                'language' => 'en',
            ]);
    }

}
