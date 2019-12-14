<?php

namespace App\Repository;

use App\Entity\Ticker;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\Query\AST\Join;
use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * @method Ticker|null find($id, $lockMode = null, $lockVersion = null)
 * @method Ticker|null findOneBy(array $criteria, array $orderBy = null)
 * @method Ticker[]    findAll()
 * @method Ticker[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TickerRepository extends ServiceEntityRepository
{
    use PagerTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Ticker::class);
    }

    public function getAll(
        int $page = 1,
        int $limit = 10,
        string $orderBy = 'ticker',
        string $sort = 'ASC',
        string $search = ''
    ): Paginator {
        $order = 't.' . $orderBy;
        // Create our query
        $queryBuilder = $this->createQueryBuilder('t')
            ->select('t')
            ->join('t.branch', 'i')
            ->leftJoin('t.researches','r')
            ->leftJoin('t.DividendMonths','d')
            ->leftJoin('t.payments','pa')
            ->groupBy('t.id')
            ->orderBy($order, $sort);

        if (!empty($search)) {
            $queryBuilder->where('t.ticker LIKE :search');
            $queryBuilder->orWhere('i.label LIKE :search');
            $queryBuilder->orWhere('t.fullname LIKE :search');
            $queryBuilder->groupBy('t.ticker');
            $queryBuilder->setParameter('search', $search . '%');
        }
        $query = $queryBuilder->getQuery();
       
        $paginator = $this->paginate($query, $page, $limit);

            

        return $paginator;
    }
    
    public function getCurrent(
        int $page = 1,
        int $limit = 10,
        string $orderBy = 'ticker',
        string $sort = 'ASC',
        string $search = ''
    ): Paginator {
        $order = 't.' . $orderBy;
        // Create our query
        $queryBuilder = $this->createQueryBuilder('t','t')
            ->select('t')
            ->addSelect('i.label')
            ->addSelect('SUM(p.amount) as units')
            ->join('t.branch', 'i')
            ->join('t.positions','p')
            ->where('p.closed <> 1')
            ->groupBy('t.id')
            ->orderBy($order, $sort);

        if (!empty($search)) {
            $queryBuilder->where('t.ticker LIKE :search');
            $queryBuilder->orWhere('i.label LIKE :search');
            $queryBuilder->orWhere('t.fullname LIKE :search');
            $queryBuilder->groupBy('t.ticker');
            $queryBuilder->setParameter('search', $search . '%');
        }
        $query = $queryBuilder->getQuery();

        $paginator = $this->paginate($query, $page, $limit);
        return $paginator;
    }

    public function getActiveUnits(Ticker $ticker): int
    {
        $queryBuilder = $this->createQueryBuilder('t')
            ->leftJoin('t.positions','p')
            ->select('SUM(p.amount) as units')
            ->where('t.id = :tickerId')
            ->andWhere('p.closed <> 1')
            ->setParameter('tickerId', $ticker->getId())
            ->getQuery();
        $result = $queryBuilder->getScalarResult();
        
        return $result[0]['units'] ?? 0;
    }

}
