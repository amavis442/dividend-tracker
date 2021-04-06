<?php

namespace App\Repository;

use App\Entity\Constants;
use App\Entity\Payment;
use App\Entity\Position;
use App\Entity\Ticker;
use App\Helper\DateHelper;
use DateTimeInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Component\Security\Core\User\UserInterface;

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

    private function getInterval(QueryBuilder $queryBuilder, string $interval)
    {
        [$startDate, $endDate] = (new DateHelper())->getInterval($interval);
        $this->setDateRange($queryBuilder, $startDate, $endDate);
    }

    private function setDateRange(QueryBuilder $queryBuilder, string $startDate, string $endDate)
    {
        $queryBuilder->andWhere('p.payDate >= :startDate and p.payDate <= :endDate');
        $queryBuilder->setParameters(['startDate' => $startDate, 'endDate' => $endDate]);
    }

    public function getAll(
        int $page = 1,
        string $interval = 'All',
        int $limit = 10,
        string $orderBy = 'exDividendDate',
        string $sort = 'DESC',
        string $search = '',
        string $startDate = null,
        string $endDate = null
    ): Paginator {
        $order = 'p.' . $orderBy;
        if ($orderBy === 'ticker') {
            $order = 't.ticker';
        }
        if ($orderBy === 'exDividendDate') {
            $order = 'c.exDividendDate';
        }

        // Create our query
        $queryBuilder = $this->createQueryBuilder('p')
            ->join('p.ticker', 't')
            ->leftJoin('p.calendar', 'c')
            ->orderBy($order, $sort);

        if ($interval !== 'All' && $startDate === null) {
            $this->getInterval($queryBuilder, $interval);
        }

        if ($startDate !== null) {
            $this->setDateRange($queryBuilder, $startDate, $endDate);
        }

        if (!empty($search)) {
            $queryBuilder->andWhere('t.ticker LIKE :search');
            $queryBuilder->setParameter('search', $search . '%');
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

    public function getTotalDividend(string $interval = 'All', string $startDate = null, string $endDate = null): ?float
    {

        $queryBuilder = $this->createQueryBuilder('p')
            ->select('SUM(p.dividend) total');

        if ($interval !== 'All' && $startDate === null) {
            $this->getInterval($queryBuilder, $interval);
        }

        if ($startDate !== null) {
            $this->setDateRange($queryBuilder, $startDate, $endDate);
        }

        $result = $queryBuilder->getQuery()
            ->getResult();

        return $result[0]['total'] / 1000;
    }

    public function getSumDividends(array $tickerIds)
    {
        $queryBuilder = $this->createQueryBuilder('p')
            ->select('SUM(p.dividend) total')
            ->addSelect('t.id')
            ->join('p.ticker', 't')
            ->where('t IN (:tickerIds)')
            ->groupBy('p.ticker')
            ->setParameter('tickerIds', $tickerIds);

        $result = $queryBuilder->getQuery()
            ->getArrayResult();
        $output = [];
        foreach ($result as $item) {
            $output[$item['id']] = $item['total'] / Constants::VALUTA_PRECISION;
        }

        return $output;
    }

    public function getDividendsPerInterval(string $interval = 'Month', UserInterface $user): array
    {
        $con = $this->getEntityManager()->getConnection();

        $sql = 'SELECT YEAR(p.pay_date) periodYear, MONTH(p.pay_date) as periodMonth, SUM(p.dividend) dividend
                from payment p WHERE p.user_id = ' . $user->getId() . ' GROUP BY YEAR(p.pay_date), MONTH(p.pay_date)';

        $result = $con->fetchAll($sql);

        $sql = 'SELECT YEAR(MIN(p.pay_date)) as startdate from payment p WHERE p.user_id = ' . $user->getId() . ' GROUP BY YEAR(p.pay_date) LIMIT 1';
        $years = $con->fetchAll($sql);
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
