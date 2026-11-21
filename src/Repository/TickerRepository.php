<?php

namespace App\Repository;

use App\Entity\Ticker;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * @method Ticker|null find($id, $lockMode = null, $lockVersion = null)
 * @method Ticker|null findOneBy(array $criteria, array $orderBy = null)
 * @method Ticker[]    findAll()
 * @method Ticker[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TickerRepository extends ServiceEntityRepository
{
    use SafeSortTrait;
    use PagerTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Ticker::class);
    }

    public function getAll(
        int $page = 1,
        int $limit = 10,
        string $orderBy = "symbol",
        string $sort = "ASC",
        ?Ticker $ticker = null
    ): Paginator {
        $order = $this->sortField($orderBy, 't', 'symbol');
        // Create our query
        $queryBuilder = $this->createQueryBuilder("t")
            ->select("t")
            ->join("t.branch", "i")
            ->leftJoin("t.researches", "r")
            ->leftJoin("t.dividendMonths", "d")
            ->leftJoin("t.payments", "pa")
            ->groupBy("t.id")
            ->orderBy($order, self::sortDirection($sort));

        if ($ticker && $ticker->getId()) {
            $queryBuilder->where("t = :ticker");
            $queryBuilder->groupBy("t.id, t.symbol");
            $queryBuilder->setParameter("ticker", $ticker->getId());
        }
        $query = $queryBuilder->getQuery();

        $paginator = $this->paginate($query, $page, $limit);

        return $paginator;
    }

    public function getAllQuery(
        string $sort = "symbol",
        string $orderBy = "ASC",
        ?Ticker $ticker = null
    ): QueryBuilder {
        $sort = $this->sortField($sort, 't', 'symbol');
        // Create our query
        $queryBuilder = $this->createQueryBuilder("t")
            ->select("t")
            ->join("t.branch", "i")
            ->leftJoin("t.researches", "r")
            ->leftJoin("t.dividendMonths", "d")
            ->leftJoin("t.payments", "pa")
            ->groupBy("t.id")
            ->orderBy($sort, self::sortDirection($orderBy));

        if ($ticker && $ticker->getId()) {
            $queryBuilder->where("t = :ticker");
            $queryBuilder->groupBy("t.id, t.symbol");
            $queryBuilder->setParameter("ticker", $ticker->getId());
        }

        return $queryBuilder;
    }

    public function getActiveUnits(Ticker $ticker): int
    {
        $queryBuilder = $this->createQueryBuilder("t")
            ->leftJoin("t.positions", "p")
            ->select("SUM(p.amount) as units")
            ->where("t.id = :tickerId")
            ->andWhere("p.closed = false")
            ->setParameter("tickerId", $ticker->getId())
            ->getQuery();
        $result = $queryBuilder->getScalarResult();

        return $result[0]["units"] ?? 0;
    }

    public function getActive(): mixed
    {
        $qb = $this->createQueryBuilder("t", "t.symbol")
            ->select("t")
            ->innerJoin("t.positions", "p")
            ->where(
                "EXISTS (SELECT 1 FROM App\Entity\Position pos WHERE pos.ticker = t.id AND (pos.closed = false))"
            )
            ->groupBy("t.id")
            ->getQuery();

        return $qb->getResult();
    }

    public function getActiveForDividendYield(): mixed
    {
        return $this->createQueryBuilder("t")
            ->select("t, p, c, dm")
            ->innerJoin("t.positions", "p")
            ->leftJoin("t.dividendMonths", "dm")
            ->leftJoin("t.calendars", "c")
            ->where("p.closed = false")
            ->orderBy("t.symbol")
            ->getQuery()
            ->getResult();
    }

    public function getTaxForTickers(array $tickers): array
    {
        return $this->createQueryBuilder('t', 't.id')
        ->select('t, ta')
        ->join('t.tax', 'ta')
        ->where('t in (:tickers)')
        ->setParameter('tickers', $tickers)
        ->getQuery()
        ->getResult();
    }
}
