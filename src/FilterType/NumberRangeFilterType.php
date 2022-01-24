<?php

namespace Unlooped\GridBundle\FilterType;

use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormEvent;
use Unlooped\GridBundle\Entity\FilterRow;

class NumberRangeFilterType extends AbstractFilterType
{
    protected $template = '@UnloopedGrid/filter_types/number_range.html.twig';

    public function handleFilter(QueryBuilder $qb, FilterRow $filterRow, array $options = []): void
    {
        if ($filterRow->getOperator() !== self::EXPR_IN_RANGE) {
            parent::handleFilter($qb, $filterRow);
            return;
        }

        $suffix = uniqid('', false);

        $field    = $this->getFieldInfo($qb, $filterRow);
        $metaData = $filterRow->getMetaData();

        if (\array_key_exists('from', $metaData) && $fromValue = $metaData['from']) {
            $qb->andWhere($qb->expr()->gte($field, ':value_start_'.$suffix));
            $qb->setParameter('value_start_'.$suffix, $fromValue);
        }
        if (\array_key_exists('to', $metaData) && $toValue = $metaData['to']) {
            $qb->andWhere($qb->expr()->lte($field, ':value_end_'.$suffix));
            $qb->setParameter('value_end_'.$suffix, $toValue);
        }
    }

    public function buildForm($builder, array $options = [], $data = null): void
    {
        $builder
            ->remove('value')
            ->add('_number_from', NumberType::class, [
                'mapped'   => false,
                'required' => false,
            ])
            ->add('_number_to', NumberType::class, [
                'mapped'   => false,
                'required' => false,
            ])
        ;
    }

    public function getFormFieldNames(): array
    {
        return [
            '_number_from',
            '_number_to',
        ];
    }

    public function postSetFormData($builder, array $options = [], $data = null, FormEvent $event = null): void
    {
        $this->buildForm($builder, $options, $data);

        $metaData = $data->getMetaData();

        if (\array_key_exists('from', $metaData)) {
            $builder->get('_number_from')->setData($metaData['from']);
        }
        if (\array_key_exists('to', $metaData)) {
            $builder->get('_number_to')->setData($metaData['to']);
        }
    }

    public function postFormSubmit($builder, array $options = [], $data = null, FormEvent $event = null): void
    {
        $data->setMetaData([
            'operator' => $data->getOperator(),
            'from'     => $builder->get('_number_from')->getData(),
            'to'       => $builder->get('_number_to')->getData(),
        ]);
    }

    protected static function getAvailableOperators(): array
    {
        return [
            self::EXPR_IN_RANGE => self::EXPR_IN_RANGE,
            self::EXPR_IS_EMPTY => self::EXPR_IS_EMPTY,
        ];
    }
}
