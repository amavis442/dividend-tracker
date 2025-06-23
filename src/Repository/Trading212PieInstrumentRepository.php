<?php

namespace App\Repository;

use App\Entity\Ticker;
use App\Entity\Trading212PieInstrument;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Trading212PieInstrument>
 */
class Trading212PieInstrumentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Trading212PieInstrument::class);
    }

    public function updateTicker(Ticker $ticker, string $symbol): void
    {
        $qb = $this->createQueryBuilder('t')
        ->update()
        ->set('t.ticker', $ticker->getId())
        ->where('t.tickerName = :symbol')
        ->setParameter(':symbol', $symbol)
        ->getQuery();
        $qb->execute();
    }

    /**
     * @return array<int,<float,float,\DateTimeImmutable>>
     */
    public function findByTicker(Ticker $ticker): array
    {
        return $this->createQueryBuilder('t')
        ->select('DATE(t.createdAt) createdAt,SUM(t.priceAvgInvestedValue) invested, SUM(t.priceAvgValue) value, SUM(t.ownedQuantity) quantity')
        ->where('t.ticker = :ticker')
        ->groupBy('createdAt')
        ->orderBy('createdAt','ASC')
        ->setParameter('ticker', $ticker->getId())
        ->getQuery()->getResult();

    }

    //    /**
    //     * @return Trading212PieInstrument[] Returns an array of Trading212PieInstrument objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('t')
    //            ->andWhere('t.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('t.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Trading212PieInstrument
    //    {
    //        return $this->createQueryBuilder('t')
    //            ->andWhere('t.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
