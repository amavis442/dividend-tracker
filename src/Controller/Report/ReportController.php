<?php

namespace App\Controller\Report;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\BranchRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Repository\PositionRepository;
use App\Repository\PaymentRepository;

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

    /**
     * @Route("/chart", name="report_chart")
     */
    public function chart(
        PaymentRepository $paymentRepository,
        PositionRepository $positionRepository
    ): Response {
        
        $data = $paymentRepository->getDividendsPerInterval();

        $labels = '['.implode(',',array_keys($data)).']';
        $accumulative = '[';
        $dividends = '[';
        foreach ($data as $item) {
            $dividends .= ($item['dividend']/100).',';
            $accumulative .= ($item['accumulative']/100).',';
        }
        $dividends .= ']';
        $accumulative .= ']';

        $totalAllocated = $positionRepository->getSumAllocated();
       
        $allocationData = $positionRepository->getAllocationData();
        $allocationLabels = '[';
        $allocatedPercentage = '[';
        foreach ($allocationData as $allocationItem) {
            $allocationLabels .= "'".$allocationItem['industry']."',";
            $allocation = $allocationItem['allocation'] / 100;
            $allocatedPercentage .= round(($allocation/$totalAllocated) * 100,2).',';
        }
        $allocationLabels = trim($allocationLabels,','). ']';
        $allocatedPercentage =  trim($allocatedPercentage,',').']';

        return $this->render('report/chart/index.html.twig', [
            'data' => $data,
            'labels' => $labels,
            'dividends' => $dividends,
            'accumulative' => $accumulative,
            'allocationLabels' => $allocationLabels,
            'allocatedPercentage' => $allocatedPercentage,
            'controller_name' => 'ReportController',
        ]);
    }
}
