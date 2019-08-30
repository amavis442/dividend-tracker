<?php

namespace App\Repository;

use App\Entity\Branch;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * @method Branch|null find($id, $lockMode = null, $lockVersion = null)
 * @method Branch|null findOneBy(array $criteria, array $orderBy = null)
 * @method Branch[]    findAll()
 * @method Branch[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BranchRepository extends ServiceEntityRepository
{
    use PagerTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Branch::class);
    }

    public function getAll(int $page = 1, int $limit = 10): Paginator
    {
        // Create our query
        $query = $this->createQueryBuilder('i')
        ->orderBy('i.label', 'DESC')
        ->getQuery();

        $paginator = $this->paginate($query, $page, $limit);

        return $paginator;

    }

    public function getReport(): ?array
    {
        $query = $this->createQueryBuilder('i')
            ->join('i.ticker','t')
            ->join('i.ticker.position','p')
            ->join('i.ticker.payment','d')

        ->orderBy('p.label', 'DESC')
        ->getQuery(); 
    }

    // /**
    //  * @return Branch[] Returns an array of Branch objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('b.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Branch
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
