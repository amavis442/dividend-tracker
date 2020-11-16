<?php

namespace App\Repository;

use App\Entity\Pie;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Pie|null find($id, $lockMode = null, $lockVersion = null)
 * @method Pie|null findOneBy(array $criteria, array $orderBy = null)
 * @method Pie[]    findAll()
 * @method Pie[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PieRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Pie::class);
    }

    // /**
    //  * @return Pie[] Returns an array of Pie objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('p.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Pie
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
