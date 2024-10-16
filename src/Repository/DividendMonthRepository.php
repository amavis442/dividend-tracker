<?php

namespace App\Repository;

use App\Entity\DividendMonth;
use App\Entity\Position;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method DividendMonth|null find($id, $lockMode = null, $lockVersion = null)
 * @method DividendMonth|null findOneBy(array $criteria, array $orderBy = null)
 * @method DividendMonth[]    findAll()
 * @method DividendMonth[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DividendMonthRepository extends ServiceEntityRepository
{
	public function __construct(ManagerRegistry $registry)
	{
		parent::__construct($registry, DividendMonth::class);
	}

	public function getAll(): array
	{
		$result = $this->createQueryBuilder('d', 'd.dividendMonth')
			->select('d,t')
			->innerJoin('d.tickers', 't', null, null, 't.symbol')
			->where(
				'EXISTS (SELECT 1 FROM ' .
					Position::class .
					' p WHERE p.ticker = t and (p.closed = false))'
			)
			->orderBy('d.dividendMonth, t.symbol', 'ASC')
			->getQuery()
			->getResult();
		return $result;
	}

	public function getFreq(array $ticker_ids)
	{
		$em = $this->getEntityManager();
		$expr = $this->getEntityManager()->getExpressionBuilder();

		$queryBuilder = $this->createQueryBuilder('d')
			->select('t.id as tickerId, COUNT(d.id) as freq')
			->join('d.tickers', 't', null, null, 't.id')
			->where($expr->in('t.id', ':tickerIds'))
			->groupBy('t.id')
			->setParameter(':tickerIds', $ticker_ids)
			->getQuery();

		return $queryBuilder->getResult();
	}

	// /**
	//  * @return DividendMonth[] Returns an array of DividendMonth objects
	//  */
	/*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('d.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

	/*
    public function findOneBySomeField($value): ?DividendMonth
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
