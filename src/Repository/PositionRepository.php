<?php

namespace App\Repository;

use App\Entity\Pie;
use App\Entity\Position;
use App\Entity\Ticker;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * @method Position|null find($id, $lockMode = null, $lockVersion = null)
 * @method Position|null findOneBy(array $criteria, array $orderBy = null)
 * @method Position[]    findAll()
 * @method Position[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PositionRepository extends ServiceEntityRepository
{
    use PagerTrait;
    public const OPEN = 1;
    public const CLOSED = 2;
    public const ALL = 3;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Position::class);
    }

    public function getAll(
        int $page = 1,
        int $limit = 10,
        string $orderBy = 't.ticker',
        string $sort = 'ASC',
        string $search = '',
        int $status = self::OPEN,
        array $pies = null
    ): Paginator {

        $queryBuilder = $this->getQueryBuilder($orderBy, $sort, $search);
        if ($status === self::OPEN) {
            $queryBuilder->andWhere('p.closed <> 1 or p.closed is null');
        }
        if ($status === self::CLOSED) {
            $queryBuilder->andWhere('p.closed = 1');
        }
        if ($pies) {
            $queryBuilder
                ->andWhere('pies IN (:pies)')
                ->setParameter('pies', $pies);
        }
        $query = $queryBuilder->getQuery();
        $paginator = $this->paginate($query, $page, $limit);

        return $paginator;
    }

    public function getForTicker(Ticker $ticker, int $status = self::OPEN): ?Position
    {
        $queryBuilder = $this->createQueryBuilder('p')
            ->select('p, tr, pa')
            ->innerJoin('p.ticker', 't')
            ->innerJoin('t.branch', 'i')
            ->leftJoin('p.transactions', 'tr')
            ->leftJoin('p.payments', 'pa')
            ->where('t = :ticker');
        if ($status === self::OPEN) {
            $queryBuilder->andWhere('p.closed <> 1 or p.closed is null');
        }
        if ($status === self::CLOSED) {
            $queryBuilder->andWhere('p.closed = 1');
        }
        return $queryBuilder->orderBy('tr.transactionDate', 'DESC')
            ->setParameter('ticker', $ticker)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function getForPosition(Position $position): ?Position
    {
        $queryBuilder = $this->createQueryBuilder('p')
            ->select('p, tr')
            ->innerJoin('p.ticker', 't')
            ->innerJoin('t.branch', 'i')
            ->leftJoin('p.transactions', 'tr')
            ->leftJoin('p.payments', 'pa')
            ->where('p = :position');

        return $queryBuilder->orderBy('tr.transactionDate', 'DESC')
            ->setParameter('position', $position)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function getAllClosed(
        int $page = 1,
        int $limit = 10,
        string $orderBy = 't.ticker',
        string $sort = 'ASC',
        string $search = ''
    ): Paginator {
        $order = $orderBy;
        if (in_array($orderBy, ['t.ticker', 'c.exDividendDate'])) {
            $order = $orderBy;
        }
        $queryBuilder = $this->getQueryBuilder($orderBy, $sort, $search);
        $queryBuilder->andWhere('p.closed = 1');
        $query = $queryBuilder->getQuery();
        $paginator = $this->paginate($query, $page, $limit);

        return $paginator;
    }

    public function getAllOpen(int $pieId = null): array
    {
        $qb = $this->createQueryBuilder('p')
            ->select('p, t, pa, c, dm')
            ->innerJoin('p.ticker', 't')
            ->leftJoin('t.calendars', 'c')
            ->leftJoin('p.payments', 'pa')
            ->leftJoin('t.DividendMonths', 'dm')
            ->where('p.closed = 0 OR p.closed IS NULL');
        
        if ($pieId) {
            $qb->join("p.pies", 'pie')
            ->andWhere('pie IN (:pieIds)')
            ->setParameter('pieIds', [$pieId]);
        }

        return $qb->getQuery()
            ->getResult();
    }

    private function getQueryBuilder(
        string $orderBy = 't.ticker',
        string $sort = 'ASC',
        string $search = ''
    ): QueryBuilder {
        $order = 't.ticker';
        if (in_array($orderBy, ['t.ticker', 't.fullname', 'i.label'])) {
            $order = $orderBy;
        }

        // Create our query
        $queryBuilder = $this->createQueryBuilder('p')
            ->select('p, t, i, pa, pies, c, dm')
            ->innerJoin('p.ticker', 't')
            ->innerJoin('t.branch', 'i')
            ->leftJoin('p.payments', 'pa')
            ->leftJoin('p.pies', 'pies')
            ->leftJoin('t.calendars', 'c')
            ->leftJoin('t.DividendMonths', 'dm')
            ->orderBy($order, $sort);

        if (!empty($search)) {
            $queryBuilder->andWhere($queryBuilder->expr()->orX(
                $queryBuilder->expr()->like('t.ticker', ':search'),
                $queryBuilder->expr()->like('i.label', ':search')
            ));
            $queryBuilder->setParameter('search', $search . '%');
        }
        return $queryBuilder;
    }

    public function getSummary(
        string $orderBy = 'ticker',
        string $sort = 'ASC',
        string $search = ''
    ): ?array{
        $order = 'p.' . $orderBy;
        if ($orderBy === 'ticker') {
            $order = 't.ticker';
        }
        $queryBuilder = $this->createQueryBuilder('p')
            ->select([
                't.ticker',
                'COUNT(p) as totalPositions',
                'SUM(p.allocation) sumAllocation',
                'SUM(p.amount) sumAmount',
                'AVG(p.price) avgPrice',
                'SUM(pa.dividend) sumDividend',
            ])
            ->innerJoin('p.ticker', 't')
            ->leftJoin('t.payments', 'pa')
            ->groupBy('t.ticker')
            ->orderBy($order, $sort)
            ->where('p.closed <> 1 or p.closed is null');
        if (!empty($search)) {
            $queryBuilder->andWhere('t.ticker LIKE :search');
            $queryBuilder->setParameter('search', $search . '%');
        }
        return $queryBuilder->getQuery()->getResult();
    }

    public function getTotalPositions(): int
    {
        $count = $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->where('p.closed <> 1  or p.closed is null')
            ->getQuery()
            ->getSingleScalarResult();
        return $count;
    }

    public function getTotalClosedPositions(): int
    {
        $count = $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->where('p.closed = 1')
            ->getQuery()
            ->getSingleScalarResult();
        return $count;
    }

    public function getTotalTickers(): int
    {
        $count = $this->createQueryBuilder('p')
            ->select('COUNT(DISTINCT p.ticker)')
            ->where('p.closed <> 1  or p.closed is null')
            ->getQuery()
            ->getSingleScalarResult();
        return $count;
    }

    public function getTotalClosedTickers(): int
    {
        $count = $this->createQueryBuilder('p')
            ->select('COUNT(DISTINCT p.ticker)')
            ->where('p.closed = 1')
            ->getQuery()
            ->getSingleScalarResult();
        return $count;
    }

    public function getProfit(): int
    {
        $profit = $this->createQueryBuilder('p')
            ->select('SUM(p.profit)')
            ->getQuery()
            ->getSingleScalarResult();
        return $profit ?? 0;
    }

    public function getSumAllocated(int $pieId = null): int
    {
        $qb = $this->createQueryBuilder('p')
            ->select('SUM(p.allocation)')
            ->where('p.closed <> 1 or p.closed is null');

        if ($pieId) {
            $qb->join("p.pies", 'pie')
            ->andWhere('pie IN (:pieIds)')
            ->setParameter('pieIds', [$pieId]);
        }

        $allocated = $qb->getQuery()
        ->getSingleScalarResult();   
        if ($allocated) {
            return $allocated / 1000;
        }
        return 0;
    }

    public function getUpcommingDividend()
    {
        return $this->createQueryBuilder('p')
            ->join('p.ticker', 't')
            ->join('t.calendars', 'c')
            ->where('c.paymentDate >= :currentDate')
            ->andWhere('p.closed <> 1')
            ->groupBy('t')
            ->orderBy('c.paymentDate', 'DESC')
            ->setParameter('currentDate', (new DateTime())->format('Y-m-d'))
            ->getQuery()
            ->getResult();
    }

    public function getAllocationsAndUnits(array $tickerIds): array
    {
        $result = $this->createQueryBuilder('p')
            ->select([
                'p.price',
                'p.amount',
                'IDENTITY(p.ticker) as tickerId',
            ])

            ->where('p.closed <> 1')
            ->join("p.ticker", 't')
            ->andWhere('t IN (:tickerIds)')
            ->setParameter('tickerIds', $tickerIds)
            ->getQuery()->getArrayResult();

        $output = [];
        foreach ($result as $item) {
            if (!isset($output[$item['tickerId']])) {
                $output[$item['tickerId']] = [];
                $output[$item['tickerId']]['allocation'] = 0;
                $output[$item['tickerId']]['units'] = 0;
            }
            $price = $item['price'] / 1000;
            $amount = $item['amount'] / 10000000;

            $allocation = $price * $amount;
            $output[$item['tickerId']]['allocation'] += $allocation;
            $output[$item['tickerId']]['amount'] += $amount;
        }
        return $output;
    }

    public function getAllocationDataPerSector(): array
    {
        return $this->createQueryBuilder('p')
            ->select([
                'b.id',
                'b.label as industry',
                'SUM(p.amount) amount',
                'p.price',
                'SUM(p.allocation) allocation',
            ])
            ->join('p.ticker', 't')
            ->join('t.branch', 'b')
            ->where('p.closed is null OR p.closed = 0')
            ->groupBy('t.branch')
            ->getQuery()
            ->getArrayResult();
    }

    public function getAllocationDataPerPosition(): array
    {
        return $this->createQueryBuilder('p')
            ->select([
                't.ticker',
                'p.allocation',
            ])
            ->innerJoin('p.ticker', 't')
            ->where('p.closed is null OR p.closed = 0')
            ->getQuery()
            ->getArrayResult();
    }
}
