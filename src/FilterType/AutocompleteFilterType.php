<?php

namespace Unlooped\GridBundle\FilterType;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Tetranz\Select2EntityBundle\Form\Type\Select2EntityType;
use Unlooped\GridBundle\Entity\FilterRow;

class AutocompleteFilterType extends AbstractFilterType
{
    private ManagerRegistry $registry;
    private PropertyAccessor $propertyAccessor;

    public function __construct(ManagerRegistry $registry, PropertyAccessor $propertyAccessor)
    {
        $this->registry         = $registry;
        $this->propertyAccessor = $propertyAccessor;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'route'                => 'unlooped_grid_autocomplete',
            'multiple'             => false,
            'multiple_expr'        => 'OR',
            'entity_primary_key'   => 'id',
            'minimum_input_length' => 2,
            'grid'                 => null,
            'grid_field'           => null,
            'text_property'        => null,
            'property'             => null,
            'filter_callback'      => null,
        ]);

        $resolver->setRequired('entity');
        $resolver->setRequired('grid');
        $resolver->setRequired('grid_field');

        $resolver->setAllowedTypes('route', 'string');
        $resolver->setAllowedTypes('entity', 'string');
        $resolver->setAllowedTypes('entity_primary_key', 'string');
        $resolver->setAllowedTypes('grid', ['string', 'null']);
        $resolver->setAllowedTypes('grid_field', ['string', 'null']);
        $resolver->setAllowedTypes('minimum_input_length', 'int');
        $resolver->setAllowedTypes('text_property', ['string', 'null']);
        $resolver->setAllowedTypes('property', ['string', 'array', 'null']);
        $resolver->setAllowedTypes('multiple', 'boolean');
        $resolver->setAllowedTypes('multiple_expr', 'string');
        $resolver->setAllowedValues('multiple_expr', ['AND', 'OR']);
        $resolver->setAllowedTypes('filter_callback', ['null', 'callable']);
    }

    public function buildForm($builder, array $options = [], $data = null): void
    {
        $builder
            ->add('value', Select2EntityType::class, [
                'multiple'             => $options['multiple'],
                'remote_route'         => $options['route'],
                'remote_params'        => [
                    'grid'  => $options['grid'],
                    'field' => $options['grid_field'],
                ],
                'class'                => $options['entity'],
                'primary_key'          => $options['entity_primary_key'],
                'minimum_input_length' => $options['minimum_input_length'],
                'text_property'        => $options['text_property'],
                'page_limit'           => 10,
                'allow_clear'          => true,
                'delay'                => 250,
                'cache'                => true,
                'cache_timeout'        => 60000, // if 'cache' is true
                'language'             => 'en',
                'property'             => $options['property'],
            ])
        ;
    }

    public function postSetFormData($builder, array $options = [], $data = null, FormEvent $event = null): void
    {
        if (null === $event) {
            parent::postSetFormData($builder, $options, $data, $event);
            return;
        }

        $filterRow = $event->getData();

        if (!$filterRow instanceof FilterRow) {
            parent::postSetFormData($builder, $options, $data, $event);
            return;
        }

        $value = $filterRow->getValue();
        if (true === $options['multiple']) {
            if (!is_array($value)) {
                parent::postSetFormData($builder, $options, $data, $event);
                return;
            }

            $entity = [];
            foreach ($value as $val) {
                $entity[] = $this->getEntityById($options, $val);
            }

            $entity = array_filter($entity);
        } else {
            if (is_object($value)) {
                parent::postSetFormData($builder, $options, $data, $event);
                return;
            }

            $entity = $this->getEntityById($options, $value);
        }

        $filterRow->setValue($entity);

        parent::postSetFormData($builder, $options, $data, $event);
    }

    public function postFormSubmit($builder, array $options = [], $data = null, FormEvent $event = null): void
    {
        parent::postFormSubmit($builder, $options, $data, $event);

        if (null === $event) {
            return;
        }

        $filterRow = $event->getData();

        if (null === $filterRow) {
            return;
        }

        $idColumn = $options['entity_primary_key'];

        $value = $filterRow->getValue();

        if (true === $options['multiple']) {
            if (!\is_array($value)) {
                return;
            }

            $id = [];
            foreach ($value as $val) {
                $id[] = $this->propertyAccessor->getValue($val, $idColumn);
            }
            $id = array_filter($id);
        } else {
            if (!\is_object($value)) {
                return;
            }

            $id = $this->propertyAccessor->getValue($value, $idColumn);
        }

        $filterRow->setValue($id);
    }

    protected static function getAvailableOperators(): array
    {
        return [
            self::EXPR_EQ => self::EXPR_EQ,
        ];
    }

    /**
     * @param array<string, mixed> $options
     */
    private function getEntityById(array $options, $value): ?object
    {
        $class    = $options['entity'];
        $idColumn = $options['entity_primary_key'];

        $manager = $this->registry->getManagerForClass($class);

        \assert($manager instanceof EntityManager);

        return $manager->createQueryBuilder()
            ->select('e')
            ->from($class, 'e')
            ->andWhere('e.'.$idColumn.' = :value')
            ->setParameter('value', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
}
