<?php

namespace App\Repository;

use App\Entity\Journal;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Journal|null find($id, $lockMode = null, $lockVersion = null)
 * @method Journal|null findOneBy(array $criteria, array $orderBy = null)
 * @method Journal[]    findAll()
 * @method Journal[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class JournalRepository extends ServiceEntityRepository
{
    use PagerTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Journal::class);
    }

    public function findItems(int $page = 1, int $limit = 30, ?array $taxonomy = null): Paginator
    {
        $dql = $this->createQueryBuilder('j')
            ->orderBy('j.id', 'DESC');
        if (!is_null($taxonomy) && !empty($taxonomy)) {
            $dql->join('j.taxonomy', 't')
                ->where('t.id IN (:taxonomy)')
                ->setParameter('taxonomy', array_flip($taxonomy));
        }


        $query = $dql->getQuery();

        return $this->paginate($query, $page, $limit);
    }

    public function findItemsQuery(?array $taxonomy = null): \Doctrine\ORM\QueryBuilder
    {
        $queryBuilder = $this->createQueryBuilder('j')
            ->orderBy('j.id', 'DESC');
        if (!is_null($taxonomy) && !empty($taxonomy)) {
            $queryBuilder->join('j.taxonomy', 't')
                ->where('t.id IN (:taxonomy)')
                ->setParameter('taxonomy', array_flip($taxonomy));
        }

        return $queryBuilder;
    }
}
