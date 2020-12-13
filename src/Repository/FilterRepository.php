<?php

namespace Unlooped\GridBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;
use Unlooped\GridBundle\Entity\Filter;

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
            ->getOneOrNullResult()
        ;
    }

    public function findByRoute(string $route)
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.route = :route')
            ->setParameter('route', $route)
            ->getQuery()
            ->getResult()
        ;
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
            ->getOneOrNullResult()
        ;
    }
}
