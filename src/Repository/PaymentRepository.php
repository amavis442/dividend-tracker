<?php

namespace App\Repository;

use App\Entity\Payment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * @method Payment|null find($id, $lockMode = null, $lockVersion = null)
 * @method Payment|null findOneBy(array $criteria, array $orderBy = null)
 * @method Payment[]    findAll()
 * @method Payment[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PaymentRepository extends ServiceEntityRepository
{
    use PagerTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Payment::class);
    }

    public function getAll(
        int $page = 1,
        int $limit = 10,
        string $orderBy = 'exDividendDate',
        string $sort = 'DESC',
        string $search = ''
    ): Paginator {
        $order = 'p.' . $orderBy;
        if ($orderBy === 'ticker') {
            $order = 't.ticker';
        }

        // Create our query
        $queryBuilder = $this->createQueryBuilder('p')
            ->join('p.ticker', 't')
            ->orderBy($order, $sort);
        if (!empty($search)) {
            $queryBuilder->where('t.ticker LIKE :search');
            $queryBuilder->setParameter('search', $search . '%');
        }
        $query = $queryBuilder->getQuery();

        $paginator = $this->paginate($query, $page, $limit);

        return $paginator;
    }

    public function getTotalDividend(): ?float
    {
        $result = $this->createQueryBuilder('p')
            ->select('SUM(p.dividend) total')
            ->getQuery()
            ->getResult();

        return $result[0]['total'] / 100;
    }
}
