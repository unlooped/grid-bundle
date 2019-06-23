<?php

namespace Unlooped\GridBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Unlooped\GridBundle\Entity\Filter;

class FilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('rows', CollectionType::class, [
                'entry_type'    => FilterRowType::class,
                'allow_add'     => true,
                'allow_delete'  => true,
                'entry_options' => ['fields' => $options['fields']]
            ])
            ->add('filter', SubmitType::class)
        ;

        $builder->addEventListener(FormEvents::PRE_SET_DATA, static function (FormEvent $event) {
            if (null !== $event->getData()) {
                /** @var Filter $data */
                $data = $event->getData();
                $form = $event->getForm();

                $label = $data->getHash() ? 'Filter and Update' : 'Filter and Save';

                if ($data->isSaveable()) {
                    $form->add('name', null, ['attr' => ['placeholder' => 'Name']]);
                    $form->add('filter_and_save', SubmitType::class, ['label' => $label]);
                }
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Filter::class,
            'fields'     => [],
        ]);

        $resolver->setRequired('fields');
        $resolver->setAllowedTypes('fields', 'array');
    }
}
