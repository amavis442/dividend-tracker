<?php

namespace App\Repository;

use App\Entity\ImportFiles;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ImportFiles>
 *
 * @method ImportFiles|null find($id, $lockMode = null, $lockVersion = null)
 * @method ImportFiles|null findOneBy(array $criteria, array $orderBy = null)
 * @method ImportFiles[]    findAll()
 * @method ImportFiles[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ImportFilesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ImportFiles::class);
    }

//    /**
//     * @return ImportFiles[] Returns an array of ImportFiles objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('i')
//            ->andWhere('i.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('i.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?ImportFiles
//    {
//        return $this->createQueryBuilder('i')
//            ->andWhere('i.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
