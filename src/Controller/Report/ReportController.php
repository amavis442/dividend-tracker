<?php

namespace App\Controller\Report;

use App\Entity\Payment;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\BranchRepository;
use App\Repository\CalendarRepository;
use Symfony\Component\HttpFoundation\Response;
use App\Repository\PositionRepository;
use App\Repository\PaymentRepository;
use App\Repository\TickerRepository;
use App\Repository\DividendMonthRepository;
use App\Service\Summary;
use App\Service\Referer;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @Route("/dashboard/report")
 */
class ReportController extends AbstractController
{
    public const TAX_DIVIDEND = 0.15; // %
    public const EXCHANGE_RATE = 1.1; // dollar to euro

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
        $labels = [];
        $dates = array_keys($data);
        foreach ($dates as $date) {
            $labels[] = strftime('%b %Y', strtotime($date . '01'));
        }

        foreach ($data as $item) {
            $dividends[] = ($item['dividend'] / 100);
            $accumulative[] = ($item['accumulative'] / 100);
        }

        return $this->render('report/payout/index.html.twig', [
            'data' => json_encode($data),
            'labels' => json_encode($labels),
            'dividends' => json_encode($dividends),
            'accumulative' => json_encode($accumulative),
            'controller_name' => 'ReportController',
        ]);
    }

    /**
     * @Route("/allocation/sector", name="report_allocation_sector")
     */
    public function allocation(PositionRepository $positionRepository, BranchRepository $branchRepository, Summary $summary)
    {
        [$numActivePosition, $numTickers, $profit, $totalDividend, $allocated] = $summary->getSummary();

        $sectors = $branchRepository->getAllocationPerSector();
        $totalAllocated = $positionRepository->getSumAllocated();

        $allocationData = $positionRepository->getAllocationDataPerSector();
        $labels = [];
        $data = [];
        foreach ($allocationData as $allocationItem) {
            $labels[] = $allocationItem['industry'];
            $allocation = $allocationItem['allocation'] / 100;
            $data[] = round(($allocation / $totalAllocated) * 100, 2);
        }

        return $this->render('report/allocation/index.html.twig', [
            'data' => json_encode($data),
            'labels' => json_encode($labels),
            'sectors' => $sectors,
            'numActivePosition' => $numActivePosition,
            'numPosition' => $numActivePosition,
            'numTickers' => $numTickers,
            'profit' => $profit,
            'totalDividend' => $totalDividend,
            'totalInvested' => $allocated,
            'controller_name' => 'ReportController',
        ]);
    }

    /**
     * @Route("/allocation/position", name="report_allocation_position")
     */
    public function allocationPerPosition(PositionRepository $positionRepository, BranchRepository $branchRepository, Summary $summary)
    {
        [$numActivePosition, $numTickers, $profit, $totalDividend, $allocated] = $summary->getSummary();

        $totalAllocated = $positionRepository->getSumAllocated();

        $allocationData = $positionRepository->getAllocationDataPerPosition();
        $labels = [];
        $data = [];
        foreach ($allocationData as $allocationItem) {
            $labels[] = $allocationItem['ticker'];
            $allocation = $allocationItem['allocation'] / 100;
            $data[] = round(($allocation / $totalAllocated) * 100, 2);
        }

        return $this->render('report/allocation/position.html.twig', [
            'data' => json_encode($data),
            'labels' => json_encode($labels),
            'numActivePosition' => $numActivePosition,
            'numPosition' => $numActivePosition,
            'numTickers' => $numTickers,
            'profit' => $profit,
            'totalDividend' => $totalDividend,
            'totalInvested' => $allocated,
            'controller_name' => 'ReportController',
        ]);
    }

    /**
     * @Route("/projection", name="report_projection")
     */
    public function projection(
        CalendarRepository $calendarRepository,
        DividendMonthRepository $dividendMonthRepository,
        Referer $referer
    ): Response {
        $dividendEstimate = $calendarRepository->getDividendEstimate();
        $labels = [];
        $data = [];
        foreach ($dividendEstimate as $date => &$estimate) {
            $d = strftime('%B %Y', strtotime($date . '01'));
            $labels[] = $d;
            $payout = ($estimate['totaldividend'] * (1 - self::TAX_DIVIDEND)) / self::EXCHANGE_RATE;
            $data[] = round($payout, 2);
            $estimate['payout'] = $payout;
            $estimate['normaldate'] = $d;
        }
   
        $dataSource = [];
        $d = $dividendMonthRepository->getAll();
        foreach ($d as $month => $dividendMonth) {
            $paydate = sprintf("%4d%02d", date('Y'), $month);
            $normalDate = strftime('%B %Y', strtotime($paydate . '01'));
            $dataSource[$paydate] = [];
            if (!isset($dividendEstimate[$paydate])) {
                $dataSource[$paydate]['totaldividend'] = 0;
                $dataSource[$paydate]['payout'] = 0;
                $dataSource[$paydate]['normaldate'] = $normalDate;
                $dataSource[$paydate]['tickers'] = [];
                foreach ($dividendMonth->getTickers() as $ticker) {                    
                    $dataSource[$paydate]['tickers'][$ticker->getTicker()] = [
                        'units' => 0,
                        'dividend' => 0,
                        'payout' => 0,
                        'payoutdate' => '',
                        'exdividend' => '',
                        'ticker' => $ticker,
                        'calendar' => null,
                        'payment' => null
                    ];
                }
            }
            if (isset($dividendEstimate[$paydate])) {
                $item = $dividendEstimate[$paydate];
                $dataSource[$paydate]['totaldividend'] = $item['totaldividend'];
                $dataSource[$paydate]['payout'] = $item['payout'];
                $dataSource[$paydate]['normaldate'] = $normalDate;
                $dataSource[$paydate]['tickers'] = [];
                foreach ($dividendMonth->getTickers() as $ticker) {
                    if (isset($item['tickers'][$ticker->getTicker()])) {
                        $tickerData = $item['tickers'][$ticker->getTicker()];
                        $dataSource[$paydate]['tickers'][$ticker->getTicker()] = $tickerData;
                    }

                    if (!isset($item['tickers'][$ticker->getTicker()])) {
                        $dataSource[$paydate]['tickers'][$ticker->getTicker()] = [
                            'units' => 0,
                            'dividend' => 0,
                            'payout' => 0,
                            'payoutdate' => '',
                            'exdividend' => '',
                            'ticker' => $ticker,
                            'calendar' => null,
                            'payment' => null
                        ];
                    }
                }
            }
        }

        $referer->set('report_projection');

        return $this->render('report/projection/index.html.twig', [
            'data' => json_encode($data),
            'labels' => json_encode($labels),
            'datasource' => $dataSource, //$dividendEstimate,
            'controller_name' => 'ReportController',
        ]);
    }

    /**
     * @Route("/yield", name="report_dividend_yield")
     */
    public function yield(TickerRepository $tickerRepository, PositionRepository $positionRepository)
    {
        $labels = [];
        $data = [];
        $dataSource = [];

        $sumDividends = 0;
        $sumAvgPrice = 0;
        $tickers = $tickerRepository->getActiveForDividendYield();
        $allocated = $positionRepository->getSumAllocated();
        $totalDividend = 0;
        
        foreach ($tickers as $ticker) {
            $positions = $ticker->getPositions();
            $position = $positions[0];
            $price = $position->getPrice();

            $scheduleCalendar = $ticker->getDividendMonths();
            $numPayoutsPerYear = count($scheduleCalendar);
            $lastCash = 0;
            $lastDividendDate = null;
            $payCalendars = $ticker->getCalendars();

            $firstCalendarEntry = $payCalendars->first();
            $lastCalendarEntry = $payCalendars->last();

            $lastCash = $firstCalendarEntry->getCashAmount();
            $lastDividendDate = $firstCalendarEntry->getPaymentDate();
            if ($firstCalendarEntry) {
                $lastCash = $lastCalendarEntry->getCashAmount();
                $lastDividendDate = $lastCalendarEntry->getPaymentDate();
            }

            $dividendPerYear = $numPayoutsPerYear * $lastCash;

            $dividendYield = round(($dividendPerYear / $price) * 100, 2);
            $labels[] = sprintf("%s (%s)", substr(addslashes($ticker->getFullname()), 0, 8), $ticker->getTicker());
            $data[] = $dividendYield;
            $dataSource[] = [
                'ticker' => $ticker->getTicker(),
                'label' => $ticker->getFullname(),
                'yield' => $dividendYield,
                'payout' => $dividendPerYear,
                'avgPrice' => $price,
                'lastDividend' => $lastCash,
                'lastDividendDate' => $lastDividendDate,
            ];

            $sumAvgPrice += $price;
            $sumDividends += $dividendPerYear;

            $totalDividend += ($dividendPerYear * $position->getAmount()) / 10000;
        }
        
        $totalAvgYield = ($sumDividends / $sumAvgPrice) * 100;
        $dividendYieldOnCost = ($totalDividend / $allocated) * 100;

        return $this->render('report/yield/index.html.twig', [
            'data' => json_encode($data),
            'labels' => json_encode($labels),
            'datasource' => $dataSource,
            'totalAvgYield' => $totalAvgYield,
            'dividendYieldOnCost' => $dividendYieldOnCost,
            'allocated' => $allocated,
            'totalDividend' => $totalDividend,
            'controller_name' => 'ReportController',
        ]);
    }
}
