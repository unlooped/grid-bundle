<?php

namespace Unlooped\GridBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Unlooped\GridBundle\Entity\FilterRow;

/**
 * @method FilterRow|null find($id, $lockMode = null, $lockVersion = null)
 * @method FilterRow|null findOneBy(array $criteria, array $orderBy = null)
 * @method FilterRow[]    findAll()
 * @method FilterRow[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FilterRowRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, FilterRow::class);
    }

    // /**
    //  * @return FilterRow[] Returns an array of FilterRow objects
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
    public function findOneBySomeField($value): ?FilterRow
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
