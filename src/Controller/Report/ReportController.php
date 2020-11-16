<?php

namespace App\Controller\Report;

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
use App\Service\Allocation;
use App\Service\Yields;
use App\Service\Payouts;
use App\Service\Projection;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @Route("/dashboard/report")
 */
class ReportController extends AbstractController
{
    public const TAX_DIVIDEND = 0.15; // %
    public const EXCHANGE_RATE = 1.19; // dollar to euro

    /**
     * @Route("/report", name="report_index")
     */
    public function index(
        BranchRepository $branchRepository,
        PositionRepository $positionRepository,
        Allocation $allocation
    ): Response {
        $data = $allocation->allocation($branchRepository, $positionRepository);

        return $this->render('report/index.html.twig', [
            'data' => $data,
            'controller_name' => 'ReportController',
        ]);
    }

    /**
     * @Route("/payout", name="report_payout")
     */
    public function payouts(
        PaymentRepository $paymentRepository,
        Payouts $payout,
        UserInterface $user
    ): Response {
        $result = $payout->payout($paymentRepository, $user);

        return $this->render('report/payout/index.html.twig', array_merge($result, [
            'controller_name' => 'ReportController',
        ]));
    }

    /**
     * @Route("/allocation/sector", name="report_allocation_sector")
     */
    public function allocation(
        PositionRepository $positionRepository,
        BranchRepository $branchRepository,
        Summary $summary,
        Allocation $allocation
    ) {
        $result = $allocation->sector($positionRepository, $branchRepository, $summary);

        return $this->render('report/allocation/index.html.twig', array_merge($result, ['controller_name' => 'ReportController']));
    }

    /**
     * @Route("/allocation/position", name="report_allocation_position")
     */
    public function allocationPerPosition(PositionRepository $positionRepository, Summary $summary, Allocation $allocation)
    {
        $result = $allocation->position($positionRepository, $summary);

        return $this->render('report/allocation/position.html.twig', array_merge($result, ['controller_name' => 'ReportController']));
    }

    /**
     * @Route("/projection/{projectionyear<\d+>?1}", name="report_projection", methods={"GET", "POST"})
     */
    public function projection(
        PositionRepository $positionRepository,
        CalendarRepository $calendarRepository,
        DividendMonthRepository $dividendMonthRepository,
        Referer $referer,
        Projection $projection,
        int $projectionyear
    ): Response {

        $referer->set('report_projection');
        if ($projectionyear === 1) {
            $projectionyear = date('Y');
        }
        $result = $projection->projection($projectionyear, $positionRepository, $calendarRepository, $dividendMonthRepository, self::TAX_DIVIDEND, self::EXCHANGE_RATE);
        return $this->render('report/projection/index.html.twig', array_merge($result, [
            'controller_name' => 'ReportController',
            'year' => $projectionyear,
        ]));
    }

    /**
     * @Route("/yield/{orderBy}", name="report_dividend_yield")
     */
    public function yield(
        TickerRepository $tickerRepository,
        PositionRepository $positionRepository,
        string $orderBy = 'ticker',
        Yields $yields
    ) {
        $result = $yields->yield($tickerRepository, $positionRepository, $orderBy, self::EXCHANGE_RATE);
        return $this->render('report/yield/index.html.twig', array_merge($result, ['controller_name' => 'ReportController']));
    }
}
