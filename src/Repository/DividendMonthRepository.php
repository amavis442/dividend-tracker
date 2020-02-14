<?php

namespace App\Repository;

use App\Entity\DividendMonth;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method DividendMonth|null find($id, $lockMode = null, $lockVersion = null)
 * @method DividendMonth|null findOneBy(array $criteria, array $orderBy = null)
 * @method DividendMonth[]    findAll()
 * @method DividendMonth[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DividendMonthRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DividendMonth::class);
    }

    public function getAll(): array
    {
        $result = $this->createQueryBuilder('d')
            ->innerJoin('d.tickers', 't')
            ->orderBy('d.dividendMonth','ASC')
            ->getQuery()
            ->getResult();
        return $result;
    }

    // /**
    //  * @return DividendMonth[] Returns an array of DividendMonth objects
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
    public function findOneBySomeField($value): ?DividendMonth
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
