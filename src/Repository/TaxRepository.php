<?php

namespace App\Repository;

use App\Entity\Currency;
use App\Entity\Tax;
use DateTimeInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Tax|null find($id, $lockMode = null, $lockVersion = null)
 * @method Tax|null findOneBy(array $criteria, array $orderBy = null)
 * @method Tax[]    findAll()
 * @method Tax[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TaxRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Tax::class);
    }

    public function findOneValid(Currency $currency, DateTimeInterface $dateTime): ?Tax
    {
        return $this->createQueryBuilder('t')
            ->join('t.currency', 'c')
            ->where('c = :currency')
            ->andWhere('t.validFrom <= :validFrom')
            ->orderBy('t.id', 'desc')
            ->setParameters(['currency' => $currency->getId(), 'validFrom' => $dateTime->format('Y-m-d')])
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

    }

    // /**
    //  * @return Tax[] Returns an array of Tax objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('t.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Tax
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
