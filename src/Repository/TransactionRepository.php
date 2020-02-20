<?php

namespace App\Repository;

use App\Entity\Transaction;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use App\Entity\Ticker;

/**
 * @method Transaction|null find($id, $lockMode = null, $lockVersion = null)
 * @method Transaction|null findOneBy(array $criteria, array $transactionBy = null)
 * @method Transaction[]    findAll()
 * @method Transaction[]    findBy(array $criteria, array $transactionBy = null, $limit = null, $offset = null)
 */
class TransactionRepository extends ServiceEntityRepository
{
    use PagerTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Transaction::class);
    }

    private function getQueryBuilder(
        string $broker = 'All',
        string $orderBy = 'transactionDate',
        string $sort = 'ASC',
        string $search = ''
    ): QueryBuilder {
        $order = 'tr.' . $orderBy;
        if ($orderBy === 'ticker') {
            $order = 't.ticker';
        }

        // Create our query
        $queryBuilder = $this->createQueryBuilder('tr')
            ->select('tr')
            ->innerJoin('tr.ticker', 't')
            ->innerJoin('t.branch', 'i')
            ->orderBy($order, $sort);
        if ($broker <> 'All') {
            $queryBuilder->andWhere('tr.broker = :broker')
                ->setParameter('broker', $broker);
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

    public function getAll(
        int $page = 1,
        string $broker = 'Trading212',
        int $limit = 10,
        string $orderBy = 'transactionDate',
        string $sort = 'ASC',
        string $search = ''
    ): Paginator {
        $queryBuilder = $this->getQueryBuilder($broker, $orderBy, $sort, $search);
        $queryBuilder->leftJoin('t.calendars', 'c');
        $query = $queryBuilder->getQuery();
        $paginator = $this->paginate($query, $page, $limit);

        return $paginator;
    }

    public function getByTicker(Ticker $ticker)
    {
        return $this->createQueryBuilder('t')
            ->innerJoin('t.position', 'p')
            ->where('t.ticker = :ticker')
            ->orderBy('t.transactionDate, t.id', 'asc')
            ->andWhere('p.closed = 0 or p.closed is null')
            ->setParameter('ticker', $ticker)
            ->getQuery()
            ->getResult();
    }
    // /**
    //  * @return Transaction[] Returns an array of Transaction objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.exampleField = :val')
            ->setParameter('val', $value)
            ->transactionBy('o.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Transaction
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
