<?php

namespace App\Repository;

use Doctrine\ORM\QueryBuilder;
use App\Entity\ApiKey;
use App\Entity\User;
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

    public function getQueryBuilderFindByAll(): QueryBuilder
    {
        return $this->createQueryBuilder('a')->orderBy('a.id', 'ASC');
    }

    public function findByApiKeyName(string $value, ?User $user = null): ?ApiKey
    {
        $queryBuilder = $this->createQueryBuilder('a')
            ->join('a.apiKeyName', 'ak')
            ->where('ak.keyName = :val')
            ->setParameter('val', $value);

        if ($user !== null) {
            $queryBuilder
                ->andWhere('a.user = :user')
                ->setParameter('user', $user);
        }

        return $queryBuilder
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
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
