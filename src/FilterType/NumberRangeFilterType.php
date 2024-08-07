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
use Unlooped\GridBundle\Struct\DefaultFilterDataStruct;

class NumberRangeFilterType extends AbstractFilterType
{
    protected string $template = '@UnloopedGrid/filter_types/number_range.html.twig';

    public static function createDefaultRangeData(string $operator, ?int $min = null, ?int $max = null): DefaultFilterDataStruct
    {
        $dto  = parent::createDefaultData($operator, null);
        $meta = [
            'operator' => $operator,
        ];

        if ($min) {
            $meta['from'] = $min;
        }
        if ($max) {
            $meta['to'] = $max;
        }

        $dto->metaData = $meta;

        return $dto;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'min_value'        => null,
            'max_value'        => null,
            'min_range'        => null,
            'max_range'        => null,
            'from_is_nullable' => true,
            'to_is_nullable'   => true,
        ]);

        $resolver->setAllowedTypes('min_value', ['null', 'float', 'int']);
        $resolver->setAllowedTypes('max_value', ['null', 'float', 'int']);
        $resolver->setAllowedTypes('min_range', ['null', 'float', 'int']);
        $resolver->setAllowedTypes('max_range', ['null', 'float', 'int']);
        $resolver->setAllowedTypes('from_is_nullable', ['boolean']);
        $resolver->setAllowedTypes('to_is_nullable', ['boolean']);
    }

    public function handleFilter(QueryBuilder $qb, FilterRow $filterRow, array $options = []): void
    {
        if ($filterRow->getOperator() !== static::EXPR_IN_RANGE) {
            parent::handleFilter($qb, $filterRow);

            return;
        }

        $suffix = uniqid('', false);

        $metaData = $filterRow->getMetaData();

        $fromValue = $metaData['from'] ?? null;
        $toValue   = $metaData['to']   ?? null;

        if (null === $fromValue && null === $toValue) {
            return;
        }

        $field = $this->getFieldInfo($qb, $filterRow);

        if (null !== $fromValue) {
            $qb->andWhere($qb->expr()->gte($field, ':value_start_'.$suffix));
            $qb->setParameter('value_start_'.$suffix, $fromValue);
        }
        if (null !== $toValue) {
            $qb->andWhere($qb->expr()->lte($field, ':value_end_'.$suffix));
            $qb->setParameter('value_end_'.$suffix, $toValue);
        }
    }

    public function buildForm($builder, array $options = [], $data = null): void
    {
        $builder
            ->remove('value')
            ->add('_number_from', NumberType::class, [
                'mapped'      => false,
                'required'    => false,
                'constraints' => $this->getFormConstraints($builder, $options, $data, '_number_from'),
            ])
            ->add('_number_to', NumberType::class, [
                'mapped'      => false,
                'required'    => false,
                'constraints' => $this->getFormConstraints($builder, $options, $data, '_number_to'),
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

    public function postSetFormData($builder, array $options = [], $data = null, ?FormEvent $event = null): void
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

    public function postFormSubmit($builder, array $options = [], $data = null, ?FormEvent $event = null): void
    {
        $data->setMetaData([
            'operator' => $data->getOperator(),
            'from'     => $builder->get('_number_from')->getData(),
            'to'       => $builder->get('_number_to')->getData(),
        ]);
    }

    protected function getFormConstraints($builder, array $options, $data, ?string $field = null): array
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
        } else {
            if (false === $options['from_is_nullable'] && '_number_from' === $field) {
                $constraints[] = new NotNull();
            }
            if (false === $options['to_is_nullable'] && '_number_to' === $field) {
                $constraints[] = new NotNull();
            }
        }

        if (null !== $options['min_range']) {
            $constraints[] = new Callback(static function ($object, ExecutionContextInterface $context) use ($options, $data): void {
                $number_from = (float) $data['_number_from'];
                $number_to   = (float) $data['_number_to'];

                $diff        = abs($number_from - $number_to);
                if ($diff < $options['min_range']) {
                    $context->addViolation('Minimum Range is '.$options['min_range']);
                }
            });
        }

        if (null !== $options['max_range']) {
            $constraints[] = new Callback(static function ($object, ExecutionContextInterface $context) use ($options, $data): void {
                $number_from = (float) $data['_number_from'];
                $number_to   = (float) $data['_number_to'];

                $diff        = abs($number_from - $number_to);
                if ($diff > $options['max_range']) {
                    $context->addViolation('Maximum Range is '.$options['max_range']);
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
