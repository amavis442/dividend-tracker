<?php

namespace App\Repository;

use App\Entity\Calendar;
use App\Entity\Pie;
use App\Entity\Position;
use App\Entity\Ticker;
use App\Entity\Transaction;
use App\Service\DividendService;
use DateTime;
use DateTimeInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Calendar|null find($id, $lockMode = null, $lockVersion = null)
 * @method Calendar|null findOneBy(array $criteria, array $orderBy = null)
 * @method Calendar[]    findAll()
 * @method Calendar[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CalendarRepository extends ServiceEntityRepository
{
    use PagerTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Calendar::class);
    }

    public function save(Calendar $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Calendar $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function getAll(
        int $page = 1,
        int $limit = 10,
        string $orderBy = 'exDividendDate',
        string $sort = 'DESC',
        string $search = ''
    ): Paginator {
        $order = 'c.' . $orderBy;
        if ($orderBy === 'ticker') {
            $order = 't.ticker';
        }
        $queryBuilder2 = $this->getEntityManager()->createQueryBuilder()
            ->select("tp.id")
            ->from("\App\Entity\Ticker", "tp")
            ->innerJoin("\App\Entity\Position", "p")
            ->where("p.ticker = tp and p.closed = false");

        // Create our query
        $queryBuilder = $this->createQueryBuilder('c')
            ->select('c')
            ->innerJoin('c.ticker', 't')
            ->orderBy($order, $sort);
        if (!empty($search)) {
            $queryBuilder->where('t.ticker LIKE :search');
            $queryBuilder->setParameter('search', $search . '%');
        }
        $queryBuilder->where($queryBuilder->expr()->in('t.id', $queryBuilder2->getDQL()));

        $query = $queryBuilder->getQuery();
        $paginator = $this->paginate($query, $page, $limit);

        return $paginator;
    }

    public function getLastDividend(Ticker $ticker)
    {
        $queryBuilder = $this->createQueryBuilder('c')
            ->select('c')
            ->innerJoin('c.ticker', 't')
            ->where('t = :ticker')
            ->setParameter('ticker', $ticker)
            ->orderBy('c.exDividendDate', 'DESC')
            ->setMaxResults(1)
            ->getQuery();

        return $queryBuilder->getOneOrNullResult();
    }

    public function findByDate(DateTimeInterface $dateTime, Ticker $ticker, string $dividendType = Calendar::REGULAR): ?Calendar
    {
        $queryBuilder = $this->createQueryBuilder('c')
            ->select('c')
            ->innerJoin('c.ticker', 't')
            ->where('t = :ticker')
            ->andWhere('c.paymentDate <= :paydate')
            ->andWhere('c.dividendType = :dividendType')
            ->setParameter('ticker', $ticker)
            ->setParameter('paydate', $dateTime->format('Y-m-d'))
            ->setParameter('dividendType', $dividendType)
            ->orderBy('c.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery();

        return $queryBuilder->getOneOrNullResult();
    }

    private function getPositionSize(Collection $transactions, Calendar $item)
    {
        $units = 0;

        foreach ($transactions as $transaction) {
            if ($transaction->getTransactionDate() >= $item->getExdividendDate()) {
                continue;
            }
            $amount = $transaction->getAmount();
            if ($transaction->getSide() === Transaction::BUY) {
                $units += $amount;
            }
            if ($transaction->getSide() === Transaction::SELL) {
                $units -= $amount;
            }
        }

        return $units;
    }

    public function getDividendEstimate(Position $position, ?int $year = null): array
    {
        if (!$year) {
            $year = date('Y');
        }

        $qb = $this->createQueryBuilder('c')
            ->select(['c', 't', 'pa'])
            ->innerJoin('c.ticker', 't')
            ->leftJoin('c.payments', 'pa', 'WITH', 'pa.calendar = c')
            ->andWhere('YEAR(c.paymentDate) = :year')
            ->andWhere('c.ticker = :ticker')
            ->setParameter('year', $year)
            ->setParameter('ticker', $position->getTicker())
            ->getQuery();
        $result = $qb->getResult();
        $output = [];

        $transactions = $position->getTransactions();
        $ticker = $position->getTicker();

        foreach ($result as $calendar) {
            if ($calendar === null) {
                continue;
            }
            $paydate = $calendar->getPaymentDate()->format('Ym');
            if (!isset($output[$paydate])) {
                $output[$paydate] = [];
            }

            if (!isset($output[$paydate][$ticker->getTicker()])) {
                $output[$paydate]['tickers'][$ticker->getTicker()] = [];
            }
            if (!isset($output[$paydate]['grossTotalPayment'])) {
                $output[$paydate]['grossTotalPayment'] = 0.0;
            }

            $amount = $this->getPositionSize($transactions, $calendar);

            $netPayment = 0.0;
            foreach ($calendar->getPayments() as $payment) {
                $netPayment += $payment->getDividend();
            }

            $dividend = $calendar->getCashAmount();
            $output[$paydate]['tickers'][$ticker->getTicker()] = [
                'amount' => $amount,
                'dividend' => $dividend,
                'payoutdate' => $calendar->getPaymentDate()->format('d-m-Y'),
                'exdividend' => $calendar->getExdividendDate()->format('d-m-Y'),
                'ticker' => $ticker,
                'netPayment' => $netPayment,
                'calendar' => $calendar,
                'position' => $position,
            ];
        }
        return $output;
    }

    /**
     * Get the calendars between startDate and endDate
     *
     * @param DividendService $dividendService
     * @param integer $year
     * @param string|null $startDate
     * @param string|null $endDate
     * @return array|null
     */
    public function groupByMonth(DividendService $dividendService, int $year, ?string $startDate = null, ?string $endDate = null, ?Pie $pie = null): ?array
    {
        if (!$startDate) {
            $startDate = $year . '-01-01';
        }
        if (!$endDate) {
            $endDate = $year . '-12-31';
        }

        $qb = $this->createQueryBuilder('c')
            ->select('c, t, p, tr, pies, cur, tax')
            ->innerJoin('c.ticker', 't')
            ->innerJoin('t.positions', 'p', 'WITH', '(p.closed = false) OR (p.closedAt > :closedAt and p.closed = true)')
            ->leftJoin('t.tax', 'tax')
            ->leftJoin('p.transactions', 'tr')
            ->leftJoin('p.pies', 'pies')
            ->leftJoin('c.currency', 'cur')
            ->where('c.paymentDate >= :start and c.paymentDate <= :end')
            ->setParameter('start', $startDate)
            ->setParameter('end', $endDate)
            ->setParameter('closedAt', (new DateTime('-2 month'))->format('Y-m-d'));

        if ($pie) {
            $qb->andWhere('pies IN (:pie)')
                ->setParameter('pie', [$pie->getId()]);
        }
        $result = $qb->getQuery()
            ->getResult();
        if (!$result) {
            return null;
        }

        $data = [];
        $dividendService->setCummulateDividendAmount(false);
        foreach ($result as $item) {
            $positionAmount = $dividendService->getPositionAmount($item);
            if ($positionAmount < 0.001) { // filter out ones that have no amount of stocks for dividend payout
                continue;
            }
            $positionDividend = $dividendService->getTotalNetDividend($item);
            if ($positionDividend < 0.01) { // filter out ones that have no payout of dividend or to small to matter
                continue;
            }

            $ticker = $item->getTicker()->getTicker();

            $taxRate = $dividendService->getTaxRate($item);
            $exchangeRate = $dividendService->getExchangeRate($item);
            $tax = $item->getCashAmount() * $exchangeRate * $taxRate;

            $data[$item->getPaymentDate()->format('Ym')][$item->getPaymentDate()->format('j')][] = [
                'calendar' => $item,
                'ticker' => $ticker,
                'positionAmount' => $positionAmount,
                'positionDividend' => $positionDividend,
                'taxRate' => $taxRate,
                'exchangeRate' => $exchangeRate,
                'tax' => $tax,
            ];
        }
        ksort($data);
        foreach ($data as &$month) {
            ksort($month);
        }
        return $data;
    }
}
