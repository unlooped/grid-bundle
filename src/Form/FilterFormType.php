<?php

namespace Unlooped\GridBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Unlooped\GridBundle\Entity\Filter;

class FilterFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('rows', CollectionType::class, [
                'entry_type'    => FilterRowType::class,
                'allow_add'     => true,
                'allow_delete'  => true,
                'entry_options' => ['fields' => $options['fields'], 'filters' => $options['filters']],
                'by_reference'  => false,
                'label'         => false,
            ])
            ->add('showAdvancedFilter', CheckboxType::class, ['required' => false])
            ->add('filter', SubmitType::class)
        ;

        $builder->addEventListener(FormEvents::PRE_SET_DATA, static function (FormEvent $event): void {
            if (null !== $event->getData()) {
                /** @var Filter $data */
                $data = $event->getData();
                $form = $event->getForm();

                $label = $data->getHash() ? 'Filter and Update' : 'Filter and Save';

                if ($data->isSaveable() && $data->getHash()) {
                    $form->add('delete_filter', SubmitType::class, [
                        'label' => 'Yes Delete Filter',
                        'attr'  => [
                            'class' => 'btn-danger btn-block',
                        ],
                    ]);
                }

                if ($data->isSaveable()) {
                    $form->add('name', null, ['attr' => ['placeholder' => 'Name']]);
                    $form->add('filter_and_save', SubmitType::class, ['label' => $label]);
                }

                if ($data->getHash()) {
                    if ($data->isDefault()) {
                        $form->add('remove_default', SubmitType::class, [
                            'label' => 'Remove Default',
                            'attr'  => [
                                'class' => 'btn btn-sm btn-outline-danger',
                            ],
                        ]);
                    } elseif ($data->isSaveable()) {
                        $form->add('make_default', SubmitType::class, [
                            'label' => 'Make Default',
                            'attr'  => [
                                'class' => 'btn btn-sm btn-outline-primary',
                            ],
                        ]);
                    }
                }
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class'        => Filter::class,
            'fields'            => [],
            'filters'           => null,
            'available_columns' => [],
        ]);

        $resolver->setRequired('fields');
        $resolver->setAllowedTypes('fields', 'array');
        $resolver->setAllowedTypes('available_columns', 'array');
    }
}
