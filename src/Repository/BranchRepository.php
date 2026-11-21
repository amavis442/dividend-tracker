<?php

namespace App\Repository;

use Doctrine\ORM\QueryBuilder;
use App\Entity\Branch;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
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
            ->orderBy('i.label', 'ASC')
            ->getQuery();

        $paginator = $this->paginate($query, $page, $limit);

        return $paginator;
    }

    public function getAllQuery(): QueryBuilder
    {
        // Create our query
        $queryBuilder = $this->createQueryBuilder('i')
            ->orderBy('i.label', 'ASC');

        return $queryBuilder;
    }

    public function getSumAssetAllocation(): int
    {
        return $this->createQueryBuilder('i')
            ->select('SUM(i.assetAllocation)')
            ->getQuery()
            ->getSingleScalarResult();
    }
}
