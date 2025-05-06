<?php

namespace App\Repository;

use App\Entity\IncomesSharesData;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<IncomesSharesData>
 */
class IncomesSharesDataRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, IncomesSharesData::class);
    }

    public function findByDataset(Uuid $uuid): array
        {
            return $this->createQueryBuilder('i')
                ->join('i.ticker','t')
                ->andWhere('i.dataset = :dataset')
                ->setParameter('dataset', $uuid)
                ->orderBy('t.fullname', 'ASC')
                ->getQuery()
                ->getResult()
            ;
        }

    //    /**
    //     * @return IncomesSharesData[] Returns an array of IncomesSharesData objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('i')
    //            ->andWhere('i.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('i.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?IncomesSharesData
    //    {
    //        return $this->createQueryBuilder('i')
    //            ->andWhere('i.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
