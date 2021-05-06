<?php

namespace App\Repository;

use App\Entity\Calendar;
use App\Entity\Pie;
use App\Entity\Position;
use App\Entity\Ticker;
use App\Entity\Transaction;
use App\Service\DividendService;
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
        // Create our query
        $queryBuilder = $this->createQueryBuilder('c')
            ->select('c')
            ->innerJoin('c.ticker', 't')
            ->orderBy($order, $sort);
        if (!empty($search)) {
            $queryBuilder->where('t.ticker LIKE :search');
            $queryBuilder->setParameter('search', $search . '%');
        }

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

    public function findByDate(DateTimeInterface $dateTime, Ticker $ticker): ?Calendar
    {
        $queryBuilder = $this->createQueryBuilder('c')
            ->select('c')
            ->innerJoin('c.ticker', 't')
            ->where('t = :ticker')
            ->andWhere('c.paymentDate <= :paydate')
            ->setParameter('ticker', $ticker)
            ->setParameter('paydate', $dateTime->format('Y-m-d'))
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
            $amount = $amount;

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
            ->innerJoin('t.positions', 'p', 'WITH', 'p.closed is null OR p.closed = 0')
            ->leftJoin('p.tax','tax')
            ->leftJoin('p.transactions', 'tr')
            ->leftJoin('p.pies', 'pies')
            ->leftJoin('c.currency', 'cur')
            //->leftJoin('cur.taxes', 'tax', 'WITH', 'tax.validFrom <= :validFrom')
            ->where('c.paymentDate >= :start and c.paymentDate <= :end')
            ->andWhere('EXISTS ( select 1 from App\Entity\Position pos WHERE pos.ticker = t.id AND pos.closed is null OR pos.closed = 0)')
            ->setParameter('start', $startDate)
            ->setParameter('end', $endDate)
            //->setParameter('validFrom', date('Y-m-d'))
        ;

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
        foreach ($result as $item) {
            $positionAmount = $dividendService->getPositionAmount($item);
            $positionDividend = $dividendService->getTotalNetDividend($item);
            $taxRate = $dividendService->getTaxRate($item);
            $exchangeRate = $dividendService->getExchangeRate($item);
            $tax = $item->getCashAmount() * $exchangeRate * $taxRate;
            $ticker = $item->getTicker()->getTicker();
            
            $data[$item->getPaymentDate()->format('Ym')][$item->getPaymentDate()->format('j')][] = [
                'calendar' => $item,
                'ticker' => $ticker,
                'positionAmount' => $positionAmount,
                'positionDividend' => $positionDividend,
                'taxRate' => $taxRate,
                'exchangeRate' => $exchangeRate,
                'tax' => $tax
            ];
        }
        ksort($data);
        foreach ($data as &$month) {
            ksort($month);
        }
        return $data;
    }
}
