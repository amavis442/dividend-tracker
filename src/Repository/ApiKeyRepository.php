<?php

namespace App\Repository;

use App\Entity\ApiKey;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ApiKey>
 */
class ApiKeyRepository extends ServiceEntityRepository
{
	public function __construct(ManagerRegistry $registry)
	{
		parent::__construct($registry, ApiKey::class);
	}

	public function getQueryBuilderFindByAll()
	{
		return $this->createQueryBuilder('a')->orderBy('a.id', 'ASC');
	}

	public function findByApiKeyName(string $value): ?ApiKey
	{
	        return $this->createQueryBuilder('a')
			->join('a.apiKeyName', 'ak')
	            ->where('ak.keyName = :val')
	            ->setParameter('val', $value)
	            ->getQuery()
	            ->getOneOrNullResult()
	        ;
	    }

	//    /**
	//     * @return ApiKey[] Returns an array of ApiKey objects
	//     */
	//    public function findByExampleField($value): array
	//    {
	//        return $this->createQueryBuilder('a')
	//            ->andWhere('a.exampleField = :val')
	//            ->setParameter('val', $value)
	//            ->orderBy('a.id', 'ASC')
	//            ->setMaxResults(10)
	//            ->getQuery()
	//            ->getResult()
	//        ;
	//    }

	//    public function findOneBySomeField($value): ?ApiKeys
	//    {
	//        return $this->createQueryBuilder('a')
	//            ->andWhere('a.exampleField = :val')
	//            ->setParameter('val', $value)
	//            ->getQuery()
	//            ->getOneOrNullResult()
	//        ;
	//    }
}
