<?php

namespace App\Repository;

use App\Entity\Payment;
use App\Entity\Position;
use App\Entity\Ticker;
use App\Entity\User;
use App\Helper\DateHelper;
use DateTime;
use DateTimeInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Payment|null find($id, $lockMode = null, $lockVersion = null)
 * @method Payment|null findOneBy(array $criteria, array $orderBy = null)
 * @method Payment[]    findAll()
 * @method Payment[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PaymentRepository extends ServiceEntityRepository
{
    use PagerTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Payment::class);
    }

    /*
    private function getInterval(QueryBuilder $queryBuilder, string $interval)
    {
        [$startDate, $endDate] = (new DateHelper())->getInterval($interval);
        $this->setDateRange($queryBuilder, $startDate, $endDate);
    }
    */

    private function setDateRange(QueryBuilder $queryBuilder, string $startDate, string $endDate)
    {
        $queryBuilder->andWhere('p.payDate >= :startDate and p.payDate <= :endDate');
        $queryBuilder->setParameters(['startDate' => $startDate, 'endDate' => $endDate]);
    }

    public function getAll(
        int $page = 1,
        int $limit = 10,
        string $orderBy = 'exDividendDate',
        string $sort = 'DESC',
        ?Ticker $ticker = null,
        string $startDate = null,
        string $endDate = null
    ): Paginator {
        $order = 'p.' . $orderBy;
        if ($orderBy === 'symbol') {
            $order = 't.symbol';
        }
        if ($orderBy === 'exDividendDate') {
            $order = 'c.exDividendDate';
        }

        // Create our query
        $queryBuilder = $this->createQueryBuilder('p')
            ->join('p.ticker', 't')
            ->leftJoin('p.calendar', 'c')
            ->orderBy($order, $sort);

        if ($startDate !== null) {
            $this->setDateRange($queryBuilder, $startDate . " 00:00:00", $endDate . " 23:59:59");
        }

        if ($ticker && $ticker->getId()) {
            $queryBuilder->andWhere(
                't = :ticker'
            );
            $queryBuilder->setParameter('ticker', $ticker->getId());
        }
        $query = $queryBuilder->getQuery();
        $paginator = $this->paginate($query, $page, $limit);

        return $paginator;
    }

    public function getForTicker(Ticker $ticker): ?array
    {
        return $this->createQueryBuilder('p')
            ->join('p.ticker', 't')
            ->where('t = :ticker')
            ->orderBy('p.payDate', 'DESC')
            ->setParameter('ticker', $ticker)
            ->getQuery()
            ->getResult();
    }

    public function findForExport(): array
    {
        return $this->createQueryBuilder('p')
            ->select('p, c, t')
            ->innerJoin('p.calendar', 'c')
            ->innerJoin('p.ticker', 't')
            ->where('p.payDate > :payDate')
            ->setParameter('payDate', (new DateTime('-7 days'))->format('Y-m-d'))
            ->getQuery()->getResult() ?? [];
    }

    public function hasPayment(DateTimeInterface $dateTime, Ticker $ticker, string $dividendType): bool
    {
        return $this->createQueryBuilder('p')
            ->join('p.ticker', 't')
            ->where('t = :ticker')
            ->andWhere('p.dividendType = :dividendType')
            ->andWhere('p.payDate >= :paydateStart AND p.payDate <= :paydateEnd')
            ->setParameter('ticker', $ticker)
            ->setParameter('paydateStart', $dateTime->format('Y-m-d 00:00:00'))
            ->setParameter('paydateEnd', $dateTime->format('Y-m-d 23:59:59'))
            ->setParameter('dividendType', $dividendType)
            ->getQuery()
            ->getOneOrNullResult() ? true : false;
    }

    public function getForPosition(Position $position): ?array
    {
        return $this->createQueryBuilder('p')
            ->join('p.position', 'pos')
            ->where('pos = :position')
            ->orderBy('p.payDate', 'DESC')
            ->setParameter('position', $position)
            ->getQuery()
            ->getResult();
    }

    public function getTotalDividend(string $startDate = null, string $endDate = null, ?Ticker $ticker = null): ?float
    {

        $queryBuilder = $this->createQueryBuilder('p')
            ->select('SUM(p.dividend) total')
            ->join('p.ticker', 't');

        if ($startDate !== null) {
            $this->setDateRange($queryBuilder, $startDate, $endDate);
        }

        if ($ticker && $ticker->getId()) {
            $queryBuilder->andWhere('t = :ticker');
            $queryBuilder->setParameter('ticker', $ticker->getId());
        }

        $result = $queryBuilder->getQuery()
            ->getResult();

        return $result[0]['total'];
    }

    public function getSumDividends(array $tickerIds)
    {
        $queryBuilder = $this->createQueryBuilder('p')
            ->select('SUM(p.dividend) total')
            ->addSelect('t.id')
            ->join('p.ticker', 't')
            ->join(Position::class, 'pos', 'WITH', '(pos.ticker = t AND pos.closed = false)')
            ->where('t IN (:tickerIds) AND p.payDate > pos.createdAt')
            ->groupBy('p.ticker, t.id')
            ->setParameter('tickerIds', $tickerIds);

        $result = $queryBuilder->getQuery()
            ->getArrayResult();
        $output = [];
        foreach ($result as $item) {
            $output[$item['id']] = $item['total'];
        }

        return $output;
    }

    public function getDividendsPerInterval(User $user, string $interval = 'Month'): array
    {
        $con = $this->getEntityManager()->getConnection();
        $em = $this->getEntityManager();

        $qb = $this->createQueryBuilder('p')
            ->select('YEAR(p.payDate) periodYear, MONTH(p.payDate) as periodMonth, SUM(p.dividend) dividend')
            ->join('p.user', 'u')
            ->where('u.id = :userID')
            ->setParameter('userID', $user->getId())
            ->groupBy('periodYear, periodMonth')
            ->orderBy('periodYear, periodMonth');

        $result = $qb->getQuery()->getResult();

        $qb = $this->createQueryBuilder('p')
            ->select('YEAR(MIN(p.payDate)) startdate')
            ->join('p.user', 'u')
            ->where('u.id = :userID')
            ->setParameter('userID', $user->getId())
            ->setMaxResults(1);

        $years = $qb->getQuery()->getResult();

        $currentYear = date('Y');
        $startYear = $years[0]['startdate'] ?? $currentYear;

        $output = [];
        $accumulative = 0;
        foreach ($result as $item) {
            $period = $item['periodYear'] . sprintf('%02d', $item['periodMonth']);
            $output[$period]['dividend'] = (int) $item['dividend'];
            $accumulative += $item['dividend'];
            $output[$period]['accumulative'] = $accumulative;
        }

        for ($year = (int) $startYear; $year < (int) $currentYear + 1; $year++) {
            for ($i = 1; $i < 13; $i++) {
                $period = $year . sprintf('%02d', $i);
                if (!isset($output[$period])) {
                    $output[$period]['dividend'] = 0;
                    $output[$period]['accumulative'] = 0;
                }

                if ($output[$period]['accumulative'] === 0) {
                    $previousPeriod = $period;
                    if ($i > 1) {
                        $previousPeriod = $year . sprintf('%02d', ($i - 1));
                    }
                    if ($year > (int) $startYear && $i === 1) {
                        $previousPeriod = ($year - 1) . '12';
                    }
                    $output[$period]['accumulative'] = $output[$previousPeriod]['accumulative'];
                }
            }
        }
        ksort($output);
        return $output;
    }
}
