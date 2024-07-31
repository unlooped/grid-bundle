<?php

namespace Unlooped\GridBundle\Tests\Form;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Unlooped\GridBundle\Form\EntityType;
use Unlooped\GridBundle\Tests\Fixtures\TestEntity;

final class EntityTypeTest extends BaseTypeTest
{
    private ManagerRegistry $registry;
    private EntityManager $entityManager;
    private PropertyAccessor $propertyAccessor;

    protected function setUp(): void
    {
        $query        = $this->createMock(AbstractQuery::class);
        $query->method('getResult')->willReturn([
            new TestEntity(1, 'One'),
            new TestEntity(2, 'Two'),
        ]);

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->method('select')->willReturnSelf();
        $queryBuilder->method('from')->willReturnSelf();
        $queryBuilder->method('getQuery')->willReturn($query);
        $this->entityManager = $this->createMock(EntityManager::class);
        $this->entityManager->method('createQueryBuilder')
            ->willReturn($queryBuilder)
        ;

        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->registry->method('getManagerForClass')->with(TestEntity::class)
            ->willReturn($this->entityManager)
        ;
        $this->propertyAccessor = $this->createMock(PropertyAccessor::class);

        parent::setUp();
    }

    public function testPassIdAndNameToViewWithGrandParent(): void
    {
        $builder = $this->factory->createNamedBuilder('parent', FormType::class)
            ->add('child', FormType::class)
        ;
        $builder->get('child')->add('grand_child', $this->getTestedType(), [
            'class'      => TestEntity::class,
            'id_column'  => 'id',
        ]);
        $view = $builder->getForm()->createView();

        self::assertSame('parent_child_grand_child', $view['child']['grand_child']->vars['id']);
        self::assertSame('grand_child', $view['child']['grand_child']->vars['name']);
        self::assertSame('parent[child][grand_child]', $view['child']['grand_child']->vars['full_name']);
    }

    protected function create($data = null, array $options = []): FormInterface
    {
        $options = array_merge([
            'class'      => TestEntity::class,
            'id_column'  => 'id',
        ], $options);

        return $this->factory->create($this->getTestedType(), $data, $options);
    }

    protected function createNamed(string $name, $data = null, array $options = []): FormInterface
    {
        $options = array_merge([
            'class'      => TestEntity::class,
            'id_column'  => 'id',
        ], $options);

        return $this->factory->createNamed($name, $this->getTestedType(), $data, $options);
    }

    protected function createBuilder(array $parentOptions = [], array $childOptions = []): FormBuilderInterface
    {
        $childOptions = array_merge([
            'class'      => TestEntity::class,
            'id_column'  => 'id',
        ], $childOptions);

        return $this->factory
            ->createNamedBuilder('parent', FormType::class, null, $parentOptions)
            ->add('child', $this->getTestedType(), $childOptions)
        ;
    }

    protected function getTestedType(): string
    {
        return EntityType::class;
    }

    protected function getTypes(): array
    {
        return [
            new EntityType($this->registry, $this->propertyAccessor),
        ];
    }
}
