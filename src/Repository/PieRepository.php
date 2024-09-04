<?php

namespace App\Repository;

use App\Entity\Pie;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Pie|null find($id, $lockMode = null, $lockVersion = null)
 * @method Pie|null findOneBy(array $criteria, array $orderBy = null)
 * @method Pie[]    findAll()
 * @method Pie[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PieRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Pie::class);
    }

    public function findAll(): array
    {
        return $this->createQueryBuilder('p')
            ->orderBy('p.label', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findLinked(): array
    {
        return $this->createQueryBuilder('p')
            ->join('p.positions', 'pos')
            ->where('pos.closed = false')
            ->andWhere('pos.ignore_for_dividend = false')
            ->orderBy('p.label', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function getActiveLabels(): array
    {
        $pieData = $this->getEntityManager()
            ->getConnection()
            ->prepare('
        SELECT p.id, p.label
            FROM pie p, transaction t
            INNER JOIN position pos ON pos.id = t.position_id
            WHERE p.id = t.pie_id
            AND pos.closed = FALSE AND pos.ignore_for_dividend = FALSE
            GROUP BY p.id, p.label
        UNION
	    SELECT p.id, p.label
            FROM pie p, pie_position pp
            INNER JOIN position pos ON pos.id = pp.position_id
            WHERE pp.pie_id = p.id
            AND pos.closed = FALSE AND pos.ignore_for_dividend = FALSE
            GROUP BY p.id, p.label;
            ')
            ->executeQuery()
            ->fetchAllAssociative();

        $pies = [];
        $pieDefault = (new Pie())->setLabel("Please choose a Pie");
        $pieIds = [];
        foreach ($pieData as $id => $data) {
            $pieIds[] = $data['id'];
        }

        $pies = $this->createQueryBuilder('pie')->where('pie.id in (:pie)')
            ->setParameter('pie', $pieIds)->getQuery()->getResult();

        $pies = array_merge([$pieDefault], $pies);

        return $pies;
    }
}
