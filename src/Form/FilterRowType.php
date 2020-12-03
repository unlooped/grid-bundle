<?php

namespace Unlooped\GridBundle\Form;

use RuntimeException;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Unlooped\GridBundle\Entity\FilterRow;
use Unlooped\GridBundle\Filter\Filter;
use Unlooped\GridBundle\FilterType\AbstractFilterType;

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

        $builder->addEventListener(FormEvents::POST_SET_DATA, function (FormEvent $event) use ($options) {
            if (null === $event->getData()) {
                return;
            }

            /** @var FilterRow $data */
            $data = $event->getData();
            $form = $event->getForm();

            $filter = $this->getFilterFromOptions($options, $data->getField());

            if ($data->getField()) {
                $filterType = $filter->getType();
                $filterType->postSetFormData($form, $filter->getOptions(), $data, $event);
            }
        });

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) use ($options) {
            if (null === $event->getData()) {
                return;
            }

            $data    = $event->getData();
            $form    = $event->getForm();

            $filter = $this->getFilterFromOptions($options, $data['field']);

            $filterType = $filter->getType();
            $filterType->preSubmitFormData($form, $filter->getOptions(), $data, $event);
        });

        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) use ($options) {
            if (null === $event->getData()) {
                return;
            }

            /** @var FilterRow $data */
            $data    = $event->getData();
            $form    = $event->getForm();

            $filter = $this->getFilterFromOptions($options, $data->getField());

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

    private function getFilterFromOptions(array $options, ?string $field): Filter
    {
        /** @var array<string, Filter> $filters */
        $filters = $options['filters'];

        if (null === $field || null === $filters[$field]) {
            throw new RuntimeException('Type is not defined');
        }

        return $filters[$field];
    }
}
