<?php

namespace App\Repository;

use App\Entity\Calendar;
use App\Entity\Ticker;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
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

    public function getDividendEstimate(): array
    {
        $result = $this->createQueryBuilder('c')
            ->select(['c', 't', 'a'])
            ->innerJoin('c.ticker', 't')
            ->innerJoin('t.positions', 'p')
            ->innerJoin('t.transactions', 'a')
            ->where('a.transactionDate < c.exDividendDate')
            ->andWhere('p.closed <> 1 or p.closed is null')
            ->andWhere('YEAR(c.paymentDate) = :year')
            ->setParameter('year', date('Y'))
            ->getQuery()
            ->getResult();
        $output = [];
        foreach ($result as $item) {
            $paydate = $item->getPaymentDate()->format('Ym');
            if (!isset($output[$paydate])) {
                $output[$paydate] = [];
            }
            $ticker = $item->getTicker();
            $transactions = $ticker->getTransactions();
            $units = 0;

            foreach ($transactions as $transaction) {
                $amount = $transaction->getAmount();
                if ($transaction->getSide() === 1) {
                    $units += $amount;
                }
                if ($transaction->getSide() === 2) {
                    $units -= $amount;
                }
            }
            $units = $units / 100;
            if (!isset($output[$paydate][$ticker->getTicker()])) {
                $output[$paydate]['tickers'][$ticker->getTicker()] = [];
                
            }
            if (!isset($output[$paydate]['totaldividend'])){
                $output[$paydate]['totaldividend'] = 0;
            }
            
            $dividend = $item->getCashAmount() / 100;
            $output[$paydate]['tickers'][$ticker->getTicker()] = [
                'units' => $units,
                'dividend' => $dividend
            ];
            $payout = $units * $dividend;
            $output[$paydate]['totaldividend'] +=  $payout;
        }
        ksort($output);
        return $output;
    }
}
