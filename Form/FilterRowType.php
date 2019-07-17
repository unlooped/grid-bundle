<?php

namespace Unlooped\GridBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Unlooped\GridBundle\Entity\FilterRow;
use Unlooped\GridBundle\FilterType\FilterType;

class FilterRowType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('field', ChoiceType::class, [
                'choices' => array_combine($options['fields'], $options['fields'])
            ])
            ->add('operator', ChoiceType::class, [
                'choices' => FilterType::getExprList()
            ])
            ->add('value')
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class'  => FilterRow::class,
            'fields'      => [],
        ]);

        $resolver->setRequired('fields');
    }
}
