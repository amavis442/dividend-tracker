<?php

namespace App\Repository;

use App\Entity\Trading212PieMetaData;
use App\Entity\Pie;
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

	public function latest($pieIds): mixed
	{
		return $this->all()
			->setMaxResults(count($pieIds))
			->getQuery()
			->getResult();
	}

	public function getDistinctPieIds(): ?array
	{
		return $this->createQueryBuilder('t')
			->select('DISTINCT(t.trading212PieId) pieId')
			->getQuery()
			->getResult();
	}

	public function getSumAllocatedAndDistributedPerData(
		\DateTimeInterface $dt
	): ?array {
		return $this->createQueryBuilder('t')
			->select(
				't.createdAt, SUM(t.priceAvgInvestedValue) invested, SUM(t.priceAvgValue) currentvalue, SUM(t.gained) gained, SUM(t.reinvested) reinvested'
			)
			->where('t.createdAt > :dt')
			->groupBy('t.createdAt')
			->orderBy('t.createdAt', 'ASC')
			->setParameter('dt', $dt->format('Y-m-d'))
			->getQuery()
			->getResult();
	}

	public function updatePie(Pie $pie): void
	{
		if ($pie->getTrading212PieId() == null) {
			return;
		}

		$qb = $this->createQueryBuilder('t')
			->update()
			->set('t.pie', ':pieID')
			->set('t.pieName', ':label')
			->where('t.trading212PieId = :trading212PieId')
			->andWhere('t.pie is null')
			->setParameter(':trading212PieId', $pie->getTrading212PieId())
			->setParameter('label',$pie->getLabel())
			->setParameter('pieID', $pie->getId())
			->getQuery();
		$qb->execute();
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
