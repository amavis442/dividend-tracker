<?php

namespace App\Repository;

use App\Entity\Trading212PieMetaData;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr\OrderBy;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Trading212PieMetaData>
 */
class Trading212PieMetaDataRepository extends ServiceEntityRepository
{
	public function __construct(ManagerRegistry $registry)
	{
		parent::__construct($registry, Trading212PieMetaData::class);
	}

	public function all(): QueryBuilder
	{
		$orderBy = new OrderBy();
		$orderBy->add('t.createdAt', 'DESC');
		$orderBy->add('pie.label', 'ASC');
		return $this->createQueryBuilder('t')
			->leftJoin('t.pie', 'pie')
			->orderBy($orderBy);
	}

	//    /**
	//     * @return Trading212PieMetaData[] Returns an array of Trading212PieMetaData objects
	//     */
	//    public function findByExampleField($value): array
	//    {
	//        return $this->createQueryBuilder('t')
	//            ->andWhere('t.exampleField = :val')
	//            ->setParameter('val', $value)
	//            ->orderBy('t.id', 'ASC')
	//            ->setMaxResults(10)
	//            ->getQuery()
	//            ->getResult()
	//        ;
	//    }

	//    public function findOneBySomeField($value): ?Trading212PieMetaData
	//    {
	//        return $this->createQueryBuilder('t')
	//            ->andWhere('t.exampleField = :val')
	//            ->setParameter('val', $value)
	//            ->getQuery()
	//            ->getOneOrNullResult()
	//        ;
	//    }
}
