<?php

namespace App\Repository;

use App\Entity\CorporateAction;
use App\Entity\Ticker;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CorporateAction>
 */
class CorporateActionRepository extends ServiceEntityRepository
{
	public function __construct(ManagerRegistry $registry)
	{
		parent::__construct($registry, CorporateAction::class);
	}

	/**
	 * Returns querybuilder with all the events for given position
	 * @param Ticker $ticker
	 * @return QueryBuilder
	 */
	public function getBuilderFindAllByPosition(
		Ticker $ticker
	): QueryBuilder {
		return $this->createQueryBuilder('c')
			->innerJoin('c.ticker', 't')
			->where('t.id = :ticker')
			->setParameter('ticker', $ticker->getId());
	}

	/**
	 * Get all corporate actions with related ticker
	 */
	public function findAllWithPositionAndTicker(): mixed
	{
		return $this->createQueryBuilder('c')
			->innerJoin('c.ticker', 't')
			->orderBy('c.eventDate')
			->getQuery()
			->getResult();
	}

	//    /**
	//     * @return CorporateAction[] Returns an array of CorporateAction objects
	//     */
	//    public function findByExampleField($value): array
	//    {
	//        return $this->createQueryBuilder('c')
	//            ->andWhere('c.exampleField = :val')
	//            ->setParameter('val', $value)
	//            ->orderBy('c.id', 'ASC')
	//            ->setMaxResults(10)
	//            ->getQuery()
	//            ->getResult()
	//        ;
	//    }

	//    public function findOneBySomeField($value): ?CorporateAction
	//    {
	//        return $this->createQueryBuilder('c')
	//            ->andWhere('c.exampleField = :val')
	//            ->setParameter('val', $value)
	//            ->getQuery()
	//            ->getOneOrNullResult()
	//        ;
	//    }

	public function findByTickerIds(array $tickerIds): mixed
	{
		return $this->createQueryBuilder('c')
			->select('c, t')
			->innerJoin('c.ticker', 't')
			->andWhere('c.ticker IN (:tickerIds)')
			->andWhere('c.type IN (:types)')
			->orderBy('c.eventDate', 'ASC')
			->setParameter('tickerIds', $tickerIds)
			->setParameter('types',[
					CorporateAction::REVERSE_SPLIT,
					CorporateAction::SPLIT,
				])
			->getQuery()
			->getResult();

		/*
		return $this->findBy(
			[
				'position' => $tickerIds,
				'type' => [
					CorporateAction::REVERSE_SPLIT,
					CorporateAction::SPLIT,
				],
			],
			['eventDate' => 'ASC'],
		);
		*/
	}
}
