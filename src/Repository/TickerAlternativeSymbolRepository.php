<?php

namespace App\Repository;

use App\Entity\TickerAlternativeSymbol;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TickerAlternativeSymbol>
 */
class TickerAlternativeSymbolRepository extends ServiceEntityRepository
{
	public function __construct(ManagerRegistry $registry)
	{
		parent::__construct($registry, TickerAlternativeSymbol::class);
	}

	public function getQueryBuilderFindByAll(): QueryBuilder
	{
		return $this->createQueryBuilder('t')->orderBy('t.id', 'ASC');
	}

	//    /**
	//     * @return TickerAlternativeSymbol[] Returns an array of TickerAlternativeSymbol objects
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

	//    public function findOneBySomeField($value): ?TickerAlternativeSymbol
	//    {
	//        return $this->createQueryBuilder('t')
	//            ->andWhere('t.exampleField = :val')
	//            ->setParameter('val', $value)
	//            ->getQuery()
	//            ->getOneOrNullResult()
	//        ;
	//    }
}
