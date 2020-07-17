<?php

namespace App\Repository;

use App\Entity\Calendar;
use App\Entity\Ticker;
use App\Entity\Transaction;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Common\Collections\Collection;

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
            if ($transaction->getTransactionDate() >= $item->getExdividendDate()){
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

    public function getDividendEstimate(): array
    {
        $qb = $this->createQueryBuilder('c')
            ->select(['c', 't', 'a', 'pa'])
            ->innerJoin('c.ticker', 't')
            ->innerJoin('t.positions', 'p')
            ->innerJoin('t.transactions', 'a')
            ->leftJoin('c.payments','pa', 'WITH', 'pa.calendar = c' )
            ->andWhere('p.closed <> 1 or p.closed is null')
            ->andWhere('YEAR(c.paymentDate) = :year')
            ->setParameter('year', date('Y'))
            ->getQuery();
        $result = $qb->getResult();
        $output = [];

        foreach ($result as $calendar) {
            if ($calendar === null) {
                continue;
            }
            $paydate = $calendar->getPaymentDate()->format('Ym');
            if (!isset($output[$paydate])) {
                $output[$paydate] = [];
            }
            $ticker = $calendar->getTicker();
            $transactions = $ticker->getTransactions();
            
            if (!isset($output[$paydate][$ticker->getTicker()])) {
                $output[$paydate]['tickers'][$ticker->getTicker()] = [];
            }
            if (!isset($output[$paydate]['grossTotalPayment'])){
                $output[$paydate]['grossTotalPayment'] = 0.0;
            }
            
            $units = $this->getPositionSize($transactions, $calendar);
            $units = $units / 100;
            
            $netPayment = 0.0;
            foreach ($calendar->getPayments() as $payment) {
                $netPayment += $payment->getDividend() / 100;
            }

            $dividend = $calendar->getCashAmount() / 100;
            $output[$paydate]['tickers'][$ticker->getTicker()] = [
                'units' => $units,
                'dividend' => $dividend,
                'payoutdate' => $calendar->getPaymentDate()->format('d-m-Y'),
                'exdividend' => $calendar->getExdividendDate()->format('d-m-Y'),
                'ticker' => $ticker,
                'netPayment' => $netPayment,
                'calendar' => $calendar
            ];
            $output[$paydate]['grossTotalPayment'] += $units * $dividend;
        }
        ksort($output);
        return $output;
    }
}
