<?php

namespace Unlooped\GridBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Unlooped\GridBundle\Entity\FilterUserSettings;

/**
 * @method FilterUserSettings|null find($id, $lockMode = null, $lockVersion = null)
 * @method FilterUserSettings|null findOneBy(array $criteria, array $orderBy = null)
 * @method FilterUserSettings[]    findAll()
 * @method FilterUserSettings[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FilterUserSettingsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FilterUserSettings::class);
    }

    public function findOneByRouteAndUserId(string $route, string $filterHash, string $userId): ?FilterUserSettings
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.route = :route')
            ->andWhere('f.filterHash = :filterHash')
            ->andWhere('f.userIdentifier = :userIdentifier')
            ->setParameter('route', $route)
            ->setParameter('filterHash', $filterHash)
            ->setParameter('userIdentifier', $userId)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    // /**
    //  * @return FilterUserSettings[] Returns an array of FilterUserSettings objects
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
    public function findOneBySomeField($value): ?FilterUserSettings
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
