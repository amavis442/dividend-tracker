<?php

namespace App\Repository;

use App\Entity\Ticker;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
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
    
    public function getAll(int $page = 1, int $limit = 10, string $orderBy = 'ticker', string $sort = 'ASC', string $search = ''): Paginator
    {
        $order = 't.'.$orderBy;
        /* if ($orderBy === 'ticker'){
            $order = 't.ticker';
        } */

        // Create our query
        $queryBuilder = $this->createQueryBuilder('t')
        ->orderBy($order, $sort);

        if (!empty($search)) {
            $queryBuilder->where('t.ticker LIKE :search');
            $queryBuilder->setParameter('search', $search.'%');
        }
        $query = $queryBuilder->getQuery();

        $paginator = $this->paginate($query, $page, $limit);

        return $paginator;

    }

    // /**
    //  * @return Ticker[] Returns an array of Ticker objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('t.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Ticker
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
