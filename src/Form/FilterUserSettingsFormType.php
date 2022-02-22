<?php

namespace Unlooped\GridBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Unlooped\GridBundle\Entity\FilterUserSettings;

class FilterUserSettingsFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('visibleColumns', ChoiceType::class, [
                'choices'      => $options['available_columns'],
                'multiple'     => true,
                'expanded'     => true,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class'        => FilterUserSettings::class,
            'available_columns' => [],
        ]);

        $resolver->setAllowedTypes('available_columns', 'array');
    }
}
