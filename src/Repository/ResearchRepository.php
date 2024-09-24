<?php

namespace App\Repository;

use App\Entity\Research;
use App\Entity\Ticker;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\ORM\QueryBuilder;

/**
 * @method Research|null find($id, $lockMode = null, $lockVersion = null)
 * @method Research|null findOneBy(array $criteria, array $orderBy = null)
 * @method Research[]    findAll()
 * @method Research[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ResearchRepository extends ServiceEntityRepository
{
    use PagerTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Research::class);
    }

    public function getAll(
        int $page = 1,
        int $limit = 10,
        string $orderBy = 'id',
        string $sort = 'ASC',
        ?Ticker $ticker = null
    ): Paginator {
        $queryBuilder = $this->getQueryBuilder($orderBy, $sort, $ticker);
        $query = $queryBuilder->getQuery();
        $paginator = $this->paginate($query, $page, $limit);

        return $paginator;
    }

    public function getAllQuery(
        string $orderBy = 'id',
        string $sort = 'ASC',
        ?Ticker $ticker = null
    ): QueryBuilder {
        $queryBuilder = $this->getQueryBuilder($orderBy, $sort, $ticker);
        return $queryBuilder;
    }

    private function getQueryBuilder(
        string $orderBy = 'id',
        string $sort = 'ASC',
        ?Ticker $ticker = null
    ): QueryBuilder {
        $order = 'r.' . $orderBy;
        if ($orderBy === 'symbol') {
            $order = 't.symbol';
        }

        // Create our query
        $queryBuilder = $this->createQueryBuilder('r')
            ->select('r')
            ->innerJoin('r.ticker', 't')
            ->orderBy($order, $sort);

        if ($ticker && $ticker->getId()) {
            $queryBuilder->andWhere('t = :ticker');
            $queryBuilder->setParameter('ticker', $ticker->getId());
        }
        return $queryBuilder;
    }
}
