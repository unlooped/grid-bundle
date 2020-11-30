<?php

namespace Unlooped\GridBundle\FilterType;

use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Unlooped\GridBundle\Entity\FilterRow;

interface FilterType
{
    public function configureOptions(OptionsResolver $resolver): void;

    /**
     * @param FormBuilderInterface|FormInterface $builder
     * @param mixed|null                         $data
     *
     * @internal
     */
    public function buildForm($builder, array $options = [], $data = null): void;

    /**
     * @param FormBuilderInterface|FormInterface $builder
     * @param mixed|null                         $data
     *
     * @internal
     */
    public function preSubmitFormData($builder, array $options = [], $data = null, FormEvent $event = null): void;

    /**
     * @param FormBuilderInterface|FormInterface $builder
     * @param mixed|null                         $data
     *
     * @internal
     */
    public function postSetFormData($builder, array $options = [], $data = null, FormEvent $event = null): void;

    /**
     * @param FormBuilderInterface|FormInterface $builder
     * @param mixed|null                         $data
     *
     * @internal
     */
    public function postFormSubmit($builder, array $options = [], $data = null, FormEvent $event = null): void;

    /**
     * @internal
     */
    public function handleFilter(QueryBuilder $qb, FilterRow $filterRow): void;

    /**
     * @internal
     *
     * @return string[]
     */
    public function getFormFieldNames(): array;
}
