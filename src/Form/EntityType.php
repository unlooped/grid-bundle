<?php

namespace Unlooped\GridBundle\Form;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\PropertyAccessor;

final class EntityType extends AbstractType
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
        $resolver
            ->setDefaults([
                'class'                     => '',
                'choice_translation_domain' => false,
                'id_column'                 => null,
            ])
            ->setNormalizer('choices', function (OptionsResolver $resolver): array {
                $class    = $resolver['class'];
                $idColumn = $resolver['id_column'];

                $manager = $this->registry->getManagerForClass($class);

                \assert($manager instanceof EntityManager);

                $entities = $manager->createQueryBuilder()
                    ->select('e')
                    ->from($class, 'e')
                    ->getQuery()->getResult()
                ;

                return $this->buildChoices($entities, $idColumn);
            })
            ->setRequired('class')
            ->setAllowedTypes('class', 'string')
            ->setAllowedTypes('id_column', 'string')
        ;
    }

    public function getParent(): string
    {
        return ChoiceType::class;
    }

    public function getBlockPrefix(): string
    {
        return 'unlooped_entity';
    }

    /**
     * @param object[] $entities
     *
     * @return array<string, int>
     */
    private function buildChoices(array $entities, string $idColumn): array
    {
        $choices = [];

        foreach ($entities as $entity) {
            $id = $this->propertyAccessor->getValue($entity, $idColumn);

            $choices[(string) $entity] = $id;
        }

        return $choices;
    }
}
