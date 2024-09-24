<?php

namespace App\Repository;

use App\Entity\Transaction;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
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
        string $orderBy = 'transactionDate',
        string $sort = 'ASC',
        ?Ticker $ticker = null
    ): QueryBuilder {
        $order = 'tr.' . $orderBy;
        if ($orderBy === 'symbol') {
            $order = 't.symbol';
        }

        // Create our query
        $queryBuilder = $this->createQueryBuilder('tr')
            ->select('tr')
            ->innerJoin('tr.position', 'p')
            ->innerJoin('p.ticker', 't')
            ->innerJoin('t.branch', 'i')
            ->orderBy($order, $sort);

        if ($ticker) {
            $queryBuilder
                ->andWhere('t = :ticker')
                ->setParameter('ticker', $ticker->getId());
        }
        return $queryBuilder;
    }

    public function getAll(
        int $page = 1,
        int $limit = 10,
        string $orderBy = 'transactionDate',
        string $sort = 'ASC',
        ?Ticker $ticker = null
    ): Paginator {
        $queryBuilder = $this->getQueryBuilder($orderBy, $sort, $ticker);
        $queryBuilder->leftJoin('t.calendars', 'c');
        $query = $queryBuilder->getQuery();
        $paginator = $this->paginate($query, $page, $limit);

        return $paginator;
    }

    public function getAllQuery(
        string $orderBy = 'transactionDate',
        string $sort = 'ASC',
        ?Ticker $ticker = null
    ): QueryBuilder {
        $queryBuilder = $this->getQueryBuilder($orderBy, $sort, $ticker);
        $queryBuilder->leftJoin('t.calendars', 'c');
        return $queryBuilder;
    }

    public function getByTicker(Ticker $ticker)
    {
        return $this->createQueryBuilder('t')
            ->innerJoin('t.position', 'p')
            ->where('t.ticker = :ticker')
            ->orderBy('t.transactionDate, t.id', 'asc')
            ->andWhere('p.closed = false')
            ->setParameter('ticker', $ticker)
            ->getQuery()
            ->getResult();
    }

    public function getLastImportFile(): array
    {
        return $this->createQueryBuilder('t')
            ->select('t.importfile')
            ->where('t.importfile is not null')
            ->orderBy('t.id', 'desc')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
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
