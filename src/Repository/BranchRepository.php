<?php

namespace App\Repository;

use App\Entity\Branch;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * @method Branch|null find($id, $lockMode = null, $lockVersion = null)
 * @method Branch|null findOneBy(array $criteria, array $orderBy = null)
 * @method Branch[]    findAll()
 * @method Branch[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BranchRepository extends ServiceEntityRepository
{
    use PagerTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Branch::class);
    }

    public function getAll(int $page = 1, int $limit = 10): Paginator
    {
        // Create our query
        $query = $this->createQueryBuilder('i')
            ->orderBy('i.label', 'ASC')
            ->getQuery();

        $paginator = $this->paginate($query, $page, $limit);

        return $paginator;
    }

    public function getAllQuery(): \Doctrine\ORM\QueryBuilder
    {
        // Create our query
        $queryBuilder = $this->createQueryBuilder('i')
            ->orderBy('i.label', 'ASC');

        return $queryBuilder;
    }

    public function getSumAssetAllocation(): int
    {
        return $this->createQueryBuilder('i')
            ->select('SUM(i.assetAllocation)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getAllocationPerSector()
    {
        $result = $this->createQueryBuilder('s')
            ->select('s, t, p')
            ->innerJoin('s.tickers', 't')
            ->innerJoin('t.positions', 'p')
            ->where('p.closed = false')
            ->getQuery()
            ->getArrayResult();

        $output = [];
        $totalAllocation = 0;
        foreach ($result as $sector) {
            if (!isset($output[$sector['label']])) {
                $output[$sector['label']] = [
                    'sectorTargetAllocation' => round($sector['assetAllocation'] / 100, 2),
                    'sectorAllocation' => 0,
                    'sectorPositions' => []
                ];
            }
            $sectorAllocation = 0;
            foreach ($sector['tickers'] as $ticker) {
                $positionData = [];
                $positionData['positionSymbol'] = $ticker['symbol'];
                $positionData['positionLabel'] = $ticker['fullname'];
                $positionData['positionAmount'] = 0;
                $positionData['positionAllocation'] = 0;
                $positionData['positionProfit'] = 0;
                foreach ($ticker['positions'] as $position) {
                    $positionData['positionAmount'] += $position['amount'];
                    $positionData['positionAllocation'] += $position['allocation'];
                    $positionData['positionProfit'] += $position['profit'];
                    $sectorAllocation += $position['allocation'];
                    $totalAllocation += $position['allocation'];
                }
                $output[$sector['label']]['sectorPositions'][] = $positionData;
                $output[$sector['label']]['sectorAllocation'] = $sectorAllocation;
            }
        }

        foreach ($output as &$sector) {
            $sector['sectorAllocationPercentage'] = round(($sector['sectorAllocation'] / $totalAllocation) * 100, 2);
            $positionData = [];
            $positionLabels = [];
            foreach ($sector['sectorPositions'] as &$position) {
                $position['positionAllocationPercentage'] = round(($position['positionAllocation'] / $sector['sectorAllocation']) * 100, 2);
                $positionData[] = $position['positionAllocationPercentage'];
                $positionLabels[] = $position['positionSymbol'];
            }
            $sector['chartdata']['data'] = json_encode($positionData);
            $sector['chartdata']['labels'] = json_encode($positionLabels);
        }


        uasort($output, function ($a, $b) {
            $a1 = $a['sectorTargetAllocation'];
            $b1 = $b['sectorTargetAllocation'];

            if ($a1 == $b1) {
                return 0;
            }
            return ($a1 > $b1) ? -1 : 1;
        });

        //ksort($output);
        return $output;
    }
}
