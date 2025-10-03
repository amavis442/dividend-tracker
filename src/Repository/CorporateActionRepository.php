<?php

namespace App\Repository;

use App\Entity\CorporateAction;
use App\Entity\Position;
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
	 * @param Position $position
	 * @return QueryBuilder
	 */
	public function getBuilderFindAllByPosition(
		Position $position
	): QueryBuilder {
		return $this->createQueryBuilder('c')
			->innerJoin('c.position', 'p')
			->where('p.id = :position')
			->andWhere('p.closed = false')
			->setParameter('position', $position->getId());
	}

	/**
	 * Get all corporate actions with related position and ticker
	 */
	public function findAllWithPositionAndTicker(): mixed
	{
		return $this->createQueryBuilder('c')
			->innerJoin('c.position', 'p')
			->innerJoin('p.ticker', 't')
			->andWhere('p.closed = false')
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

	public function findByPositionIds(array $positionIds): array
	{
		return $this->findBy([
			[
				'position' => $positionIds,
				'type' => [
					CorporateAction::REVERSE_SPLIT,
					CorporateAction::SPLIT,
				],
			],
			['eventDate' => 'ASC'],
		]);
	}
}
