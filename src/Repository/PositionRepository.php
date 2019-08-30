<?php

namespace App\Repository;

use App\Entity\Position;
use App\Entity\Ticker;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
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

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Position::class);
    }

    public function getAll(int $page = 1, int $limit = 10, string $orderBy = 'buy_date', string $sort = 'ASC', string $search = ''): Paginator
    {
        $order = 'p.'.$orderBy;
        if ($orderBy === 'ticker'){
            $order = 't.ticker';
        }
        // Create our query
        $queryBuilder = $this->createQueryBuilder('p')
        ->select('p')
        ->innerJoin('p.ticker', 't')
        ->leftJoin('p.payments', 'pa')
        ->orderBy($order, $sort)
        ->where('p.closed <> 1 or p.closed is null');
        if (!empty($search)) {
            $queryBuilder->where('t.ticker LIKE :search');
            $queryBuilder->setParameter('search', $search.'%');
        }

        $query = $queryBuilder->getQuery();
        $paginator = $this->paginate($query, $page, $limit);

        return $paginator;
    }

    public function getSummary(int $page = 1, int $limit = 60, string $orderBy = 'ticker', string $sort = 'ASC', string $search = ''): ?array
    {
        $order = 'p.'.$orderBy;
        if ($orderBy === 'ticker'){
            $order = 't.ticker';
        }
        $queryBuilder = $this->createQueryBuilder('p')
            ->select(['t.ticker', 
                    'COUNT(p) as totalPositions',
                    'SUM(p.allocation) sumAllocation', 
                    'SUM(p.amount) sumAmount', 
                    'AVG(p.price) avgPrice', 
                    'SUM(pa.dividend) sumDividend'
                    ])
            ->innerJoin('p.ticker', 't')
            ->leftJoin('p.payments', 'pa')
            ->groupBy('t.ticker')
            ->orderBy($order, $sort)
            ->where('p.closed <> 1 or p.closed is null');
        if (!empty($search)) {
            $queryBuilder->andWhere('t.ticker LIKE :search');
            $queryBuilder->setParameter('search', $search.'%');
        }    
        return $queryBuilder->getQuery()->getResult();
    }

    public function getTotalPositions():int
    {
        $count = $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->where('p.closed <> 1  or p.closed is null')
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

    public function getProfit(): int
    {
        $profit = $this->createQueryBuilder('p')
        ->select('SUM(p.profit)')
        ->where('p.closed = 1')
        ->getQuery()
        ->getSingleScalarResult();
        return $profit;
    }

    public function getSumAllocated(): int
    {
        $allocated = $this->createQueryBuilder('p')
        ->select('SUM(p.allocation)')
        ->where('p.closed <> 1 or p.closed is null')
        ->getQuery()
        ->getSingleScalarResult();
        return $allocated;
    }


    // /**
    //  * @return Position[] Returns an array of Position objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('p.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Position
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
