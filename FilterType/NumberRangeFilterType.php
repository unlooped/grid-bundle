<?php

namespace Unlooped\GridBundle\FilterType;

use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormInterface;
use Unlooped\GridBundle\Entity\FilterRow;

class NumberRangeFilterType extends FilterType
{

    protected $template = '@UnloopedGrid/filter_types/number_range.html.twig';

    public static function getAvailableOperators(): array
    {
        return [
            self::EXPR_IN_RANGE => self::EXPR_IN_RANGE,
        ];
    }

    public function handleFilter(QueryBuilder $qb, FilterRow $filterRow): void
    {
        $i = self::$cnt++;

        $field = $this->getFieldInfo($qb, $filterRow);
        $metaData = $filterRow->getMetaData();

        $fromValue = $metaData['from'];
        $toValue = $metaData['to'];

        if ($fromValue) {
            $qb->andWhere($qb->expr()->gte($field, ':value_start_' . $i));
            $qb->setParameter('value_start_' . $i, $fromValue);
        }

        if ($toValue) {
            $qb->andWhere($qb->expr()->lte($field, ':value_end_' . $i));
            $qb->setParameter('value_end_' . $i, $toValue);
        }
    }

    /**
     * @param FormBuilderInterface|FormInterface $builder
     * @param FilterRow|array $data
     * @param array $options
     */
    public function buildForm($builder, array $options = [], $data = null): void
    {
        $builder
            ->add('_number_from', NumberType::class, [
                'mapped' => false,
                'required' => false,
            ])
            ->add('_number_to', NumberType::class, [
                'mapped' => false,
                'required' => false,
            ])
            ->remove('value')
        ;
    }

    /**
     * @param FormBuilderInterface|FormInterface $builder
     * @param array $options
     * @param FilterRow $data
     * @param FormEvent|null $event
     */
    public function postSetFormData($builder, array $options = [], $data = null, FormEvent $event = null): void
    {
        $this->buildForm($builder, [], $data);

        $builder->get('_number_from')->setData($data->getMetaData()['number_from']);
        $builder->get('_number_to')->setData($data->getMetaData()['number_to']);
    }

    /**
     * @param FormBuilderInterface|FormInterface $builder
     * @param array $options
     * @param FilterRow $data
     * @param FormEvent|null $event
     */
    public function postFormSubmit($builder, array $options = [], $data = null, FormEvent $event = null): void
    {
        $data->setMetaData([
            'from' => $builder->get('_number_from')->getData(),
            'to'   => $builder->get('_number_to')->getData(),
        ]);
    }

}
