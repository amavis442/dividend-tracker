<?php

namespace App\Repository;

use App\Entity\Ticker;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
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
        $order = "t." . $orderBy;
        // Create our query
        $queryBuilder = $this->createQueryBuilder("t")
            ->select("t")
            ->join("t.branch", "i")
            ->leftJoin("t.researches", "r")
            ->leftJoin("t.dividendMonths", "d")
            ->leftJoin("t.payments", "pa")
            ->groupBy("t.id")
            ->orderBy($order, $sort);

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
    ): \Doctrine\ORM\QueryBuilder {
        $sort = "t." . $sort;
        // Create our query
        $queryBuilder = $this->createQueryBuilder("t")
            ->select("t")
            ->join("t.branch", "i")
            ->leftJoin("t.researches", "r")
            ->leftJoin("t.dividendMonths", "d")
            ->leftJoin("t.payments", "pa")
            ->groupBy("t.id")
            ->orderBy($sort, $orderBy);

        if ($ticker && $ticker->getId()) {
            $queryBuilder->where("t = :ticker");
            $queryBuilder->groupBy("t.id, t.symbol");
            $queryBuilder->setParameter("ticker", $ticker->getId());
        }

        return $queryBuilder;
    }

    public function getCurrent(
        int $page = 1,
        int $limit = 10,
        string $orderBy = "branch.label",
        string $sort = "ASC",
        string $search = ""
    ): Paginator {
        [$orderTable, $orderColumn] = explode(".", $orderBy);

        $order = "t." . $orderColumn;
        if ($orderTable == "ticker") {
            $order = "t." . $orderColumn;
        }
        if ($orderTable == "branch") {
            $order = "i." . $orderColumn;
        }

        // Create our query
        $queryBuilder = $this->createQueryBuilder("t", "t")
            ->select("t")
            ->addSelect("i.label")
            ->addSelect("p.amount as units")
            ->addSelect("p.allocation as invested")
            ->join("t.branch", "i")
            ->join("t.positions", "p")
            ->where("p.closed = false")
            ->groupBy("t.id")
            ->orderBy($order, $sort);

        if (!empty($search)) {
            $queryBuilder->andWhere(
                $queryBuilder
                    ->expr()
                    ->orX(
                        "t.symbol LIKE :search",
                        "i.label LIKE :search",
                        "t.fullname LIKE :search"
                    )
            );
            $queryBuilder->groupBy("t.symbol");
            $queryBuilder->setParameter("search", $search . "%");
        }
        $query = $queryBuilder->getQuery();

        $paginator = $this->paginate($query, $page, $limit);
        return $paginator;
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

    public function getActive()
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

    public function getActiveForDividendYield()
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
}
