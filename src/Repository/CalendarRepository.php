<?php

namespace App\Repository;

use App\Entity\Calendar;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * @method Calendar|null find($id, $lockMode = null, $lockVersion = null)
 * @method Calendar|null findOneBy(array $criteria, array $orderBy = null)
 * @method Calendar[]    findAll()
 * @method Calendar[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CalendarRepository extends ServiceEntityRepository
{
    use PagerTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Calendar::class);
    }

    public function getAll(
        int $page = 1,
        int $limit = 10,
        string $orderBy = 'exDividendDate',
        string $sort = 'DESC',
        string $search = ''
    ): Paginator {
        $order = 'c.' . $orderBy;
        if ($orderBy === 'ticker') {
            $order = 't.ticker';
        }
        // Create our query
        $queryBuilder = $this->createQueryBuilder('c')
            ->select('c')
            ->innerJoin('c.ticker', 't')
            ->orderBy($order, $sort);
        if (!empty($search)) {
            $queryBuilder->where('t.ticker LIKE :search');
            $queryBuilder->setParameter('search', $search . '%');
        }

        $query = $queryBuilder->getQuery();
        $paginator = $this->paginate($query, $page, $limit);

        return $paginator;
    }
}
