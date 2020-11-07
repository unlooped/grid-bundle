<?php

namespace Unlooped\GridBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\NonUniqueResultException;
use Unlooped\GridBundle\Entity\Filter;

/**
 * @method Filter|null find($id, $lockMode = null, $lockVersion = null)
 * @method Filter|null findOneBy(array $criteria, array $orderBy = null)
 * @method Filter[]    findAll()
 * @method Filter[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FilterRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Filter::class);
    }

    /**
     * @throws NonUniqueResultException
     */
    public function findOneByHash(string $hash): ?Filter
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.hash = :hash')
            ->setParameter('hash', $hash)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findByRoute(string $route)
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.route = :route')
            ->setParameter('route', $route)
            ->getQuery()
            ->getResult();
    }

    public function findDefaultForRoute(string $route): ?Filter
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.route = :route')
            ->andWhere('f.isDefault = :isDefault')
            ->setParameter('route', $route)
            ->setParameter('isDefault', true)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    // /**
    //  * @return Filter[] Returns an array of Filter objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('f.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Filter
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
