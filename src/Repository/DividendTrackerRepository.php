<?php

namespace App\Repository;

use App\Entity\DividendTracker;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method DividendTracker|null find($id, $lockMode = null, $lockVersion = null)
 * @method DividendTracker|null findOneBy(array $criteria, array $orderBy = null)
 * @method DividendTracker[]    findAll()
 * @method DividendTracker[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DividendTrackerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DividendTracker::class);
    }

    // /**
    //  * @return DividendTracker[] Returns an array of DividendTracker objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('d.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?DividendTracker
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
