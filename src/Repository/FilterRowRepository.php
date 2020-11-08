<?php

namespace Unlooped\GridBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Unlooped\GridBundle\Entity\FilterRow;

/**
 * @method FilterRow|null find($id, $lockMode = null, $lockVersion = null)
 * @method FilterRow|null findOneBy(array $criteria, array $orderBy = null)
 * @method FilterRow[]    findAll()
 * @method FilterRow[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FilterRowRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FilterRow::class);
    }
}
