<?php

namespace Unlooped\GridBundle\FilterType;

use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\LessThanOrEqual;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Unlooped\GridBundle\Entity\FilterRow;

class NumberRangeFilterType extends AbstractFilterType
{
    protected $template = '@UnloopedGrid/filter_types/number_range.html.twig';

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'min_value' => null,
            'max_value' => null,
            'min_range' => null,
            'max_range' => null,
        ]);

        $resolver->setAllowedTypes('min_value', ['null', 'float', 'int']);
        $resolver->setAllowedTypes('max_value', ['null', 'float', 'int']);
        $resolver->setAllowedTypes('min_range', ['null', 'float', 'int']);
        $resolver->setAllowedTypes('max_range', ['null', 'float', 'int']);
    }

    public function handleFilter(QueryBuilder $qb, FilterRow $filterRow, array $options = []): void
    {
        if ($filterRow->getOperator() !== static::EXPR_IN_RANGE) {
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
        $constraints = $this->getFormConstraints($builder, $options, $data);

        $builder
            ->remove('value')
            ->add('_number_from', NumberType::class, [
                'mapped'      => false,
                'required'    => false,
                'constraints' => $constraints,
            ])
            ->add('_number_to', NumberType::class, [
                'mapped'      => false,
                'required'    => false,
                'constraints' => $constraints,
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

    protected function getFormConstraints($builder, array $options, $data): array
    {
        $constraints = [];
        if (null !== $options['min_value']) {
            $constraints[] = new GreaterThanOrEqual($options['min_value']);
        }
        if (null !== $options['max_value']) {
            $constraints[] = new LessThanOrEqual($options['max_value']);
        }
        if (null !== $options['min_range'] || null !== $options['max_range']) {
            $constraints[] = new NotNull();
        }
        if (null !== $options['min_range']) {
            $constraints[] = new Callback(static function ($object, ExecutionContextInterface $context) use ($options, $data): void {
                $number_from = (float)$data['_number_from'];
                $number_to   = (float)$data['_number_to'];

                $diff        = abs($number_from - $number_to);
                if ($diff < $options['min_range']) {
                    $context->addViolation('Minimum Range is '.$options['min_range'], [], null);
                }
            });
        }
        if (null !== $options['max_range']) {
            $constraints[] = new Callback(static function ($object, ExecutionContextInterface $context) use ($options, $data): void {
                $number_from = (float)$data['_number_from'];
                $number_to   = (float)$data['_number_to'];

                $diff        = abs($number_from - $number_to);
                if ($diff > $options['max_range']) {
                    $context->addViolation('Maximum Range is '.$options['max_range'], [], null);
                }
            });
        }

        return $constraints;
    }

    protected static function getAvailableOperators(): array
    {
        return [
            static::EXPR_IN_RANGE => static::EXPR_IN_RANGE,
            static::EXPR_IS_EMPTY => static::EXPR_IS_EMPTY,
        ];
    }
}
