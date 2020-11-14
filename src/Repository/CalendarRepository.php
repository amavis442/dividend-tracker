<?php

namespace App\Repository;

use App\Entity\Calendar;
use App\Entity\Position;
use App\Entity\Ticker;
use App\Entity\Transaction;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\Tools\Pagination\Paginator;

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
            $amount = $amount / 10000000;

            $netPayment = 0.0;
            foreach ($calendar->getPayments() as $payment) {
                $netPayment += $payment->getDividend() / 1000;
            }

            $dividend = $calendar->getCashAmount() / 1000;
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
}
