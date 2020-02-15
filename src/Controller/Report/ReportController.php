<?php

namespace App\Controller\Report;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\BranchRepository;
use App\Repository\CalendarRepository;
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
     * @Route("/payout", name="report_payout")
     */
    public function payouts(
        PaymentRepository $paymentRepository
    ): Response {
        
        $data = $paymentRepository->getDividendsPerInterval();
        
        $labels = '[';
        $dates = array_keys($data);
        foreach ($dates as $date)
        {
            $labels .= "'".strftime('%b %Y',strtotime($date.'01'))."',";
        }
        $labels = trim($labels,','). ']';

        $accumulative = '[';
        $dividends = '[';
        foreach ($data as $item) {
            $dividends .= ($item['dividend']/100).',';
            $accumulative .= ($item['accumulative']/100).',';
        }
        $dividends .= ']';
        $accumulative .= ']';

        return $this->render('report/payout/index.html.twig', [
            'data' => $data,
            'labels' => $labels,
            'dividends' => $dividends,
            'accumulative' => $accumulative,
            'controller_name' => 'ReportController',
        ]);
    }

    /**
     * @Route("/allocation", name="report_allocation")
     */
    public function allocation(PositionRepository $positionRepository, BranchRepository $branchRepository)
    {
        $sectors = $branchRepository->getAllocationPerSector();
        $totalAllocated = $positionRepository->getSumAllocated();
       
        $allocationData = $positionRepository->getAllocationDataPerSector();
        $labels = '[';
        $data = '[';
        foreach ($allocationData as $allocationItem) {
            $labels .= "'".$allocationItem['industry']."',";
            $allocation = $allocationItem['allocation'] / 100;
            $data .= round(($allocation/$totalAllocated) * 100,2).',';
        }
        $labels = trim($labels,','). ']';
        $data =  trim($data,',').']';

        return $this->render('report/allocation/index.html.twig', [
            'data' => $data,
            'labels' => $labels,
            'sectors' => $sectors,
            'controller_name' => 'ReportController',
        ]);
    }


    /**
     * @Route("/projection", name="report_projection")
     */
    public function projection(
        CalendarRepository $calendarRepository
    ): Response {
        $dividendEstimate = $calendarRepository->getDividendEstimate();

        $labels = '[';
        $data = '[';
        foreach ($dividendEstimate as $date => $estimate) {
            $d = strftime('%B %Y',strtotime($date.'01'));
            $labels .= "'".$d."',";
            $payout = ($estimate['totaldividend'] * 0.85) / 1.1;
            $data .= round($payout, 2).',';
        }
        $labels = trim($labels,','). ']';
        $data =  trim($data,',').']';

        return $this->render('report/projection/index.html.twig', [
            'data' => $data,
            'labels' => $labels,
            'controller_name' => 'ReportController',
        ]);
    }
}
