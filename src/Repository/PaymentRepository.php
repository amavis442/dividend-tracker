<?php

namespace App\Repository;

use App\Entity\Payment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use App\Helper\DateHelper;

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

    private function getInterval(QueryBuilder $queryBuilder, int $interval)
    {
        [$startDate, $endDate] = (new DateHelper())->getInterval($interval);
        $queryBuilder->andWhere('p.payDate >= :startDate and p.payDate <= :endDate');
        $queryBuilder->setParameters(['startDate' => $startDate, 'endDate' => $endDate]);
    }

    public function getAll(
        int $page = 1,
        int $limit = 10,
        string $orderBy = 'exDividendDate',
        string $sort = 'DESC',
        string $search = '',
        int $interval = 0
    ): Paginator {
        $order = 'p.' . $orderBy;
        if ($orderBy === 'ticker') {
            $order = 't.ticker';
        }
        if ($orderBy === 'exDividendDate') {
            $order = 'c.exDividendDate';
        }

        // Create our query
        $queryBuilder = $this->createQueryBuilder('p')
            ->join('p.ticker', 't')
            ->leftJoin('p.calendar', 'c')
            ->orderBy($order, $sort);

        if ($interval > 0) {
            $this->getInterval($queryBuilder, $interval);
        }
        if (!empty($search)) {
            $queryBuilder->where('t.ticker LIKE :search');
            $queryBuilder->setParameter('search', $search . '%');
        }
        $query = $queryBuilder->getQuery();

        $paginator = $this->paginate($query, $page, $limit);

        return $paginator;
    }

    public function getTotalDividend(int $interval = 0): ?float
    {
        $queryBuilder = $this->createQueryBuilder('p')
            ->select('SUM(p.dividend) total');

        if ($interval > 0) {
            $this->getInterval($queryBuilder, $interval);
        }

        $result = $queryBuilder->getQuery()
            ->getResult();

        return $result[0]['total'] / 100;
    }

    public function getTotalDividendClosed(): ?float
    {
        $result = $this->createQueryBuilder('p')
            ->select('SUM(p.dividend) total')
            ->innerJoin('p.position', 'po')
            ->where('po.closed = 1')
            ->getQuery()
            ->getResult();

        return $result[0]['total'] / 100;
    }
}
