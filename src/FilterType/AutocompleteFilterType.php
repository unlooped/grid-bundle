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
            'route'                => '',
            'entity'               => '',
            'entity_primary_key'   => 'id',
            'text_property'        => null,
            'minimum_input_length' => 2,
        ]);

        $resolver->setRequired('route');
        $resolver->setRequired('entity');
        $resolver->setRequired('minimum_input_length');

        $resolver->setAllowedTypes('route', 'string');
        $resolver->setAllowedTypes('entity', 'string');
        $resolver->setAllowedTypes('minimum_input_length', 'int');
        $resolver->setAllowedTypes('text_property', ['string', 'null']);
    }

    public function buildForm($builder, array $options = [], $data = null): void
    {
        $builder
            ->add('value', Select2EntityType::class, [
                'multiple'             => false,
                'remote_route'         => $options['route'],
                'class'                => $options['entity'],
                'primary_key'          => $options['entity_primary_key'],
                'minimum_input_length' => 1,
                'text_property'        => $options['text_property'],
                'page_limit'           => 10,
                'allow_clear'          => true,
                'delay'                => 250,
                'cache'                => true,
                'cache_timeout'        => 60000, // if 'cache' is true
                'language'             => 'en',
            ])
        ;
    }

    public function postSetFormData($builder, array $options = [], $data = null, FormEvent $event = null): void
    {
        if (null === $event) {
            return;
        }

        $filterRow = $event->getData();

        if (null === $filterRow) {
            return;
        }

        \assert($filterRow instanceof FilterRow);

        if (\is_object($filterRow->getValue())) {
            return;
        }

        $filterRow->setValue($this->getEntityById($options, $filterRow->getValue()));

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

        \assert($filterRow instanceof FilterRow);

        $idColumn = $options['entity_primary_key'];

        if (!\is_object($filterRow->getValue())) {
            return;
        }

        $id = $this->propertyAccessor->getValue($filterRow->getValue(), $idColumn);

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
