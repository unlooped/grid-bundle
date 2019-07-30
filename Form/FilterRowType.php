<?php

namespace Unlooped\GridBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
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

        $builder->addEventListener(FormEvents::POST_SET_DATA, static function (FormEvent $event) use ($options) {
            if (null !== $event->getData()) {
                /** @var FilterRow $data */
                $data = $event->getData();
                $filters = $options['filters'];

                if ($data->getField()) {
                    /** @var FilterType $filterType */
                    $filterType = $filters[$data->getField()];
                    $filterType->postSetFormData($event);
                }
            }
        });

        $builder->addEventListener(FormEvents::PRE_SUBMIT, static function (FormEvent $event) use ($options) {
            if (null !== $event->getData()) {
                $data = $event->getData();
                $form = $event->getForm();
                $filters = $options['filters'];

                /** @var FilterType $filterType */
                $filterType = $filters[$data['field']];
                $filterType->buildForm($form, $options, $data);
            }
        });

        $builder->addEventListener(FormEvents::POST_SUBMIT, static function (FormEvent $event) use ($options) {
            if (null !== $event->getData()) {
                /** @var FilterRow $data */
                $data = $event->getData();
                $filters = $options['filters'];

                /** @var FilterType $filterType */
                $filterType = $filters[$data->getField()];
                $filterType->postFormSubmit($event);
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class'  => FilterRow::class,
            'fields'      => [],
            'filters'      => null,
        ]);

        $resolver->setRequired('fields');
    }
}
