<?php

namespace App\Repository;

use App\Entity\Position;
use App\Entity\Pie;
use App\Entity\Ticker;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

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
        string $orderBy = 't.symbol',
        string $sort = 'ASC',
        ?Ticker $ticker = null,
        int $status = self::OPEN,
        ?Pie $pie = null
    ): Paginator {
        $queryBuilder = $this->getQueryBuilder($orderBy, $sort, $ticker);
        if ($status === self::OPEN) {
            $queryBuilder->andWhere('p.closed = false');
        }
        if ($status === self::CLOSED) {
            $queryBuilder->andWhere('p.closed = true');
        }
        if ($pie && $pie->getId()) {
            $queryBuilder
                ->leftJoin('p.pies', 'pies')
                ->andWhere('pies IN (:pies)')
                ->setParameter('pies', $pie->getId())
                ->addSelect('pies');
        }
        $query = $queryBuilder->getQuery();
        $paginator = $this->paginate($query, $page, $limit);

        return $paginator;
    }

    public function findOneByTicker(Ticker $ticker, int $status = self::OPEN): ?Position
    {
        $queryBuilder = $this->createQueryBuilder('p')
            ->select('p')
            ->where('p.ticker = :ticker');
        if ($status === self::OPEN) {
            $queryBuilder->andWhere('p.closed = false');
        }
        if ($status === self::CLOSED) {
            $queryBuilder->andWhere('p.closed = true');
        }
        return $queryBuilder
            ->setParameter('ticker', $ticker)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findForExport(): array
    {
        $queryBuilder = $this->createQueryBuilder('p')
            ->select('p')
            ->innerJoin('p.transactions', 't')
            ->where('p.closed = false');
        //->orWhere('p.closed = true and t.transactionDate > :closedAt')
        //->setParameter('closedAt', (new DateTime('-3 days'))->format('Y-m-d'));

        return $queryBuilder->getQuery()->getResult();
    }

    public function findOneByTickerAndTransactionDate(Ticker $ticker, ?DateTime $transactionDate = null): ?Position
    {
        $queryBuilder = $this->createQueryBuilder('p')
            ->select('p')
            ->where('p.ticker = :ticker')
            ->andWhere(':transactionDate >= p.createdAt AND :transactionDate <= p.closedAt');

        return $queryBuilder
            ->setParameter('ticker', $ticker)
            ->setParameter('transactionDate', $transactionDate)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findOneByTickerAndDate(Ticker $ticker, ?DateTime $transactionDate = null): ?Position
    {
        if ($transactionDate) {
            $position = $this->findOneByTickerAndTransactionDate($ticker, $transactionDate);
            if ($position) {
                return $position;
            }
        }
        $queryBuilder = $this->createQueryBuilder('p')
            ->select('p')
            ->where('p.ticker = :ticker')
            ->andWhere('p.closed = false');

        $position = $queryBuilder
            ->setParameter('ticker', $ticker)
            ->getQuery()
            ->getOneOrNullResult();

        return $position;
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
            $queryBuilder->andWhere('p.closed = false');
        }
        if ($status === self::CLOSED) {
            $queryBuilder->andWhere('p.closed = true');
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
        string $sort = 'DESC',
        ?Ticker $ticker = null
    ): Paginator {
        $queryBuilder = $this->createQueryBuilder('p')
            ->select('p, t, i')
            ->innerJoin('p.ticker', 't')
            ->innerJoin('t.branch', 'i')
            ->where('p.closedAt IS NOT NULL')
            ->orderBy('p.closedAt', $sort);

        if ($ticker && $ticker->getId()) {
            $queryBuilder->andWhere(
                't = :ticker'
            );
            $queryBuilder->setParameter('ticker', $ticker->getId());
        }
        $queryBuilder->andWhere('p.closed = true');
        $query = $queryBuilder->getQuery();
        $paginator = $this->paginate($query, $page, $limit);

        return $paginator;
    }

    public function getAllOpenForProjection(int $pieId = null, int $year = null): array
    {
        $qb = $this->createQueryBuilder('p')
            ->select('p, t, pa, pac, c, dm, tr')
            ->innerJoin('p.ticker', 't')
            ->leftJoin('t.calendars', 'c')
            ->leftJoin('p.transactions', 'tr')
            ->leftJoin('t.payments', 'pa')
            ->leftJoin('pa.calendar', 'pac')
            ->leftJoin('t.dividendMonths', 'dm')
            ->where('p.closed = false');

        if ($pieId) {
            $qb->join("p.pies", 'pie')
                ->andWhere('pie IN (:pieIds)')
                ->setParameter('pieIds', [$pieId]);
        }

        if ($year) {
            $qb->andWhere('YEAR(c.paymentDate) = :year')
                ->setParameter('year', $year);
        }

        return $qb->getQuery()
            ->getResult();
    }

    public function getAllOpenPaymentsForProjection(int $pieId = null, int $year = null): array
    {
        $qb = $this->createQueryBuilder('p')
            ->select('p, t, pa, pac')
            ->innerJoin('p.ticker', 't')
            ->leftJoin('t.payments', 'pa')
            ->leftJoin('pa.calendar', 'pac')
            ->where('p.closed = false');

        if ($pieId) {
            $qb->join("p.pies", 'pie')
                ->andWhere('pie IN (:pieIds)')
                ->setParameter('pieIds', [$pieId]);
        }

        if ($year) {
            $qb->andWhere('YEAR(pac.paymentDate) = :year')
                ->setParameter('year', $year);
        }

        return $qb->getQuery()
            ->getResult();
    }

    public function getAllOpen(int $pieId = null, int $year = null): array
    {
        $qb = $this->createQueryBuilder('p')
            //->select('p, t, pa, c, dm, cur, tax, b')
            ->select('p, t')
            ->innerJoin('p.ticker', 't')
            ->leftJoin('t.calendars', 'c')
            //->leftJoin('t.dividendMonths', 'dm')
            ->leftJoin('t.tax', 'tax')
            ->leftJoin('t.branch', 'b')
            ->leftJoin('p.payments', 'pa')
            ->leftJoin('c.currency', 'cur')
            ->where('p.closed = false');

        if ($pieId) {
            $qb->join("p.pies", 'pie')
                ->andWhere('pie IN (:pieIds)')
                ->setParameter('pieIds', [$pieId]);
        }

        if ($year) {
            $qb->andWhere('YEAR(c.paymentDate) = :year')
                ->setParameter('year', $year);
        }

        return $qb->getQuery()
            ->getResult();
    }

    private function getQueryBuilder(
        string $orderBy = 't.symbol',
        string $sort = 'ASC',
        ?Ticker $ticker = null
    ): QueryBuilder {
        $order = 't.symbol';
        if (in_array($orderBy, ['t.symbol', 't.fullname', 'i.label'])) {
            $order = $orderBy;
        }

        // Create our query
        $queryBuilder = $this->createQueryBuilder('p')
            ->select('p')
            ->innerJoin('p.ticker', 't')
            ->addSelect('t')
            ->leftJoin('p.payments', 'pa')
            ->addSelect('pa')
            ->leftJoin('t.tax', 'tax')
            ->addSelect('tax')
            ->leftJoin('t.calendars', 'c')
            ->addSelect('c')
            ->orderBy($order, $sort);

        if ($ticker && $ticker->getId()) {
            $queryBuilder->addSelect('b');
            $queryBuilder->innerJoin('t.branch', 'b');
            $queryBuilder->andWhere('t = :ticker');
            $queryBuilder->setParameter('ticker', $ticker->getId());
        }
        return $queryBuilder;
    }

    public function getSummary(
        string $orderBy = 'symbol',
        string $sort = 'ASC',
        string $search = ''
    ): ?array {
        $order = 'p.' . $orderBy;
        if ($orderBy === 'symbol') {
            $order = 't.symbol';
        }
        $queryBuilder = $this->createQueryBuilder('p')
            ->select([
                't.symbol',
                'COUNT(p) as totalPositions',
                'SUM(p.allocation) sumAllocation',
                'SUM(p.amount) sumAmount',
                'AVG(p.price) avgPrice',
                'SUM(pa.dividend) sumDividend',
            ])
            ->innerJoin('p.ticker', 't')
            ->leftJoin('t.payments', 'pa')
            ->groupBy('t.symbol')
            ->orderBy($order, $sort)
            ->where('p.closed = false');
        if (!empty($search)) {
            $queryBuilder->andWhere('t.symbol LIKE :search');
            $queryBuilder->setParameter('search', $search . '%');
        }
        return $queryBuilder->getQuery()->getResult();
    }

    public function getOpenPositions(): array
    {
        $result = $this->createQueryBuilder('p')
            ->where('p.closed = false')
            ->getQuery()
            ->getResult();
        return $result;
    }

    public function getTotalPositions(): int
    {
        $count = $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->where('p.closed = false')
            ->getQuery()
            ->getSingleScalarResult();
        return $count;
    }

    public function getTotalClosedPositions(): int
    {
        $count = $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->where('p.closed = true')
            ->getQuery()
            ->getSingleScalarResult();
        return $count;
    }

    public function getTotalTickers(): int
    {
        $count = $this->createQueryBuilder('p')
            ->select('COUNT(DISTINCT p.ticker)')
            ->where('p.closed = false')
            ->getQuery()
            ->getSingleScalarResult();
        return $count;
    }

    public function getTotalClosedTickers(): int
    {
        $count = $this->createQueryBuilder('p')
            ->select('COUNT(DISTINCT p.ticker)')
            ->where('p.closed = true')
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

    public function getSumAllocated(int $pieId = null): float
    {
        $qb = $this->createQueryBuilder('p')
            ->select('SUM(p.allocation)')
            ->where('p.closed = false');

        if ($pieId) {
            $qb->join("p.pies", 'pie')
                ->andWhere('pie IN (:pieIds)')
                ->setParameter('pieIds', [$pieId]);
        }

        $allocated = $qb->getQuery()
            ->getSingleScalarResult();
        if ($allocated) {
            return $allocated;
        }
        return 0;
    }

    public function getUpcommingDividend()
    {
        return $this->createQueryBuilder('p')
            ->join('p.ticker', 't')
            ->join('t.calendars', 'c')
            ->where('c.paymentDate >= :currentDate')
            ->andWhere('p.closed = false')
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

            ->where('p.closed = false')
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
            $price = $item['price'];
            $amount = $item['amount'];

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
            ->where('p.closed = false')
            ->groupBy('t.branch')
            ->getQuery()
            ->getArrayResult();
    }

    public function getAllocationDataPerPosition(): array
    {
        return $this->createQueryBuilder('p')
            ->select([
                't.symbol',
                'p.allocation',
            ])
            ->innerJoin('p.ticker', 't')
            ->where('p.closed = false')
            ->getQuery()
            ->getArrayResult();
    }
}
