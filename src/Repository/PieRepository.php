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
        return $this->createQueryBuilder("p")
            ->orderBy("p.label", "ASC")
            ->getQuery()
            ->getResult();
    }

    public function findLinked(): array
    {
        return $this->createQueryBuilder("p")
            ->join("p.positions", "pos")
            ->where("pos.closed = false")
            ->andWhere("pos.ignore_for_dividend = false")
            ->orderBy("p.label", "ASC")
            ->getQuery()
            ->getResult();
    }
}
