<?php

namespace App\Repository;

use App\Entity\Position;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use DateTime;
use Doctrine\ORM\Mapping\JoinColumn;

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

    public function getAll(
        int $page = 1,
        string $broker = 'Trading212',
        int $limit = 10,
        string $orderBy = 'buyDate',
        string $sort = 'ASC',
        string $search = ''
    ): Paginator {
        $queryBuilder = $this->getQueryBuilder($broker, $orderBy, $sort, $search);
        $queryBuilder->leftJoin('t.calendars' ,'c');
        $queryBuilder->andWhere('p.closed <> 1 or p.closed is null');
        $query = $queryBuilder->getQuery();
        $paginator = $this->paginate($query, $page, $limit);

        return $paginator;
    }

    public function getAllClosed(
        int $page = 1,
        int $limit = 10,
        string $orderBy = 'buyDate',
        string $sort = 'ASC',
        string $search = ''
    ): Paginator {
        $order = 'p.' . $orderBy;
        if ($orderBy === 'ticker') {
            $order = 't.ticker';
        }

        if ($orderBy === 'dividend') {
            $order = 'c.exDividendDate';
        }
        $queryBuilder = $this->getQueryBuilder('All', $orderBy, $sort, $search);
        $queryBuilder->andWhere('p.closed = 1');
        $query = $queryBuilder->getQuery();
        $paginator = $this->paginate($query, $page, $limit);

        return $paginator;
    }

    private function getQueryBuilder(
        string $broker = 'All',
        string $orderBy = 'buyDate',
        string $sort = 'ASC',
        string $search = ''
    ): QueryBuilder {
        $order = 'p.' . $orderBy;
        if ($orderBy === 'ticker') {
            $order = 't.ticker';
        }

        // Create our query
        $queryBuilder = $this->createQueryBuilder('p')
            ->select('p')
            ->innerJoin('p.ticker', 't')
            ->innerJoin('t.branch', 'i')
            ->leftJoin('t.payments', 'pa')
            ->orderBy($order, $sort);
        if ($broker <> 'All') {
            $queryBuilder->andWhere('p.broker = :broker')
            ->setParameter('broker' , $broker);
        }


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
    ): ?array {
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
                'SUM(pa.dividend) sumDividend'
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
        return $allocated ?? 0;
    }

    public function getUpcommingDividend()
    {
        return $this->createQueryBuilder('p')
            ->join('p.ticker', 't')
            ->join('t.calendars','c')
            ->where('c.paymentDate >= :currentDate')
            ->andWhere('p.closed <> 1')
            ->orderBy('c.paymentDate')
            ->setParameter('currentDate', (new DateTime())->format('Y-m-d'))
            ->getQuery()
            ->getResult();
    }

    public function test(array $tickerIds): array
    {
        $result =  $this->createQueryBuilder('p')
            ->select('t.id, AVG(p.price) as buy, SUM((p.amount /100) * p.price) as allocation, p.allocation as alli')
            ->where('p.closed <> 1')
            ->join("p.ticker",'t')
            ->andWhere('t IN (:tickerIds)')
            ->setParameter('tickerIds', $tickerIds)
            ->groupBy('p.ticker')
            ->getQuery()->getArrayResult();
            
            $output = [];
            foreach ($result as $item){
                $output[$item['id']] = $item;
            }
            return $output;
    }
}
