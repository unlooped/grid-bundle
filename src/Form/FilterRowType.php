<?php

namespace Unlooped\GridBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Unlooped\GridBundle\Entity\FilterRow;
use Unlooped\GridBundle\Filter\Filter;
use Unlooped\GridBundle\FilterType\AbstractFilterType;
use Unlooped\GridBundle\FilterType\FilterType;

class FilterRowType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('field', ChoiceType::class, [
                'translation_domain' => 'unlooped_grid',
                'choices'            => $options['fields'],
            ])
            ->add('operator', ChoiceType::class, [
                'translation_domain' => 'unlooped_grid',
                'choices'            => AbstractFilterType::getExprList(),
            ])
            ->add('value', null, ['required' => false])
        ;

        $builder->addEventListener(FormEvents::POST_SET_DATA, static function (FormEvent $event) use ($options) {
            if (null === $event->getData()) {
                return;
            }

            /** @var FilterRow $data */
            $data = $event->getData();
            $form = $event->getForm();

            /** @var array<string, Filter> $filters */
            $filters = $options['filters'];
            $filter  = $filters[$data->getField()];

            if ($data->getField()) {
                $filterType = $filter->getType();

                $metaData                    = $data->getMetaData();
                $metaData['_original_field'] = $data->getField();

                $data->setMetaData($metaData);
                $filterType->postSetFormData($form, $filter->getOptions(), $data, $event);
            }
        });

        $builder->addEventListener(FormEvents::SUBMIT, static function (FormEvent $event) use ($options) {
            if (null === $event->getData()) {
                return;
            }

            /** @var FilterRow $data */
            $data    = $event->getData();
            $form    = $event->getForm();

            /** @var array<string, Filter> $filters */
            $filters = $options['filters'];

            if (\array_key_exists('_original_field', $data->getMetaData()) && $data->getMetaData()['_original_field'] !== $data->getField()) {
                /** @var FilterType $originalFilterType */
                $originalFilterType = $filters[$data->getMetaData()['_original_field']];
                $origFields         = $originalFilterType->getFormFieldNames();

                /** @var FilterType $filterType */
                $filterType = $filters[$data->getField()]->getType();
                $newFields  = $filterType->getFormFieldNames();

                $fieldsToRemove = array_diff($origFields, $newFields);
                foreach ($fieldsToRemove as $fieldToRemove) {
                    $form->remove($fieldToRemove);
                }
            }
        });

        $builder->addEventListener(FormEvents::PRE_SUBMIT, static function (FormEvent $event) use ($options) {
            if (null === $event->getData()) {
                return;
            }

            $data    = $event->getData();
            $form    = $event->getForm();

            /** @var array<string, Filter> $filters */
            $filters = $options['filters'];
            $filter     = $filters[$data['field']];

            $filterType = $filter->getType();
            $filterType->preSubmitFormData($form, $filter->getOptions(), $data, $event);
        });

        $builder->addEventListener(FormEvents::POST_SUBMIT, static function (FormEvent $event) use ($options) {
            if (null === $event->getData()) {
                return;
            }

            /** @var FilterRow $data */
            $data    = $event->getData();
            $form    = $event->getForm();

            /** @var array<string, Filter> $filters */
            $filters = $options['filters'];
            $filter     = $filters[$data->getField()];

            $filterType = $filter->getType();
            $filterType->postFormSubmit($form, $filter->getOptions(), $data, $event);
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class'   => FilterRow::class,
            'fields'       => [],
            'filters'      => null,
        ]);

        $resolver->setRequired('fields');
    }
}
