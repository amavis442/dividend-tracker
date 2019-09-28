<?php

namespace App\Controller\Report;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\BranchRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Repository\PositionRepository;

/**
 * @Route("/dashboard/report")
 */
class ReportController extends AbstractController
{
    /**
     * @Route("/report", name="report_index")
     */
    public function index(
        BranchRepository $branchRepository,
        PositionRepository $positionRepository
    ): Response {
        $allocated = $positionRepository->getSumAllocated();
        $branches = $branchRepository->findAll();

        $data = [];
        foreach ($branches as $branch) {
            $item = [
                'industry' => $branch->getLabel(),
                'allocation' => 0,
                'allocationPercentage' => 0,
                'targetAllocationPercentage' => $branch->getAssetAllocation() / 100,
                'dividend' => 0,
                'tickers' => 0,
            ];

            $tickers = $branch->getTickers();
            foreach ($tickers as $tickers) {
                $item['tickers'] += 1;
                foreach ($tickers->getPositions() as $position) {
                    $item['allocation'] += $position->getAllocation();
                    $item['dividend'] += $position->getDividend();
                }
            }
            $item['allocationPercentage'] = 0;
            if ($allocated > 0) {
                $item['allocationPercentage'] = ((int) $item['allocation'] / (int) $allocated) * 100;
            }
            $data[$item['allocation']] = $item;
        }

        krsort($data);

        return $this->render('report/index.html.twig', [
            'data' => $data,
            'controller_name' => 'ReportController',
        ]);
    }
}
