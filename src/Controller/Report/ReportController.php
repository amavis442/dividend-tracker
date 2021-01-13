<?php

namespace App\Controller\Report;

use App\Repository\BranchRepository;
use App\Repository\CalendarRepository;
use App\Repository\DividendMonthRepository;
use App\Repository\PaymentRepository;
use App\Repository\PositionRepository;
use App\Repository\TickerRepository;
use App\Service\Allocation;
use App\Service\Export;
use App\Service\Payouts;
use App\Service\Projection;
use App\Service\Referer;
use App\Service\Summary;
use App\Service\Yields;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
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
        PositionRepository $positionRepository,
        Allocation $allocation
    ): Response {
        $data = $allocation->allocation($positionRepository);

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
        //$result = $allocation->sector($positionRepository, $branchRepository, $summary);
        $result = $allocation->allocation($positionRepository);
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
     * @Route("/projection/{projectionyear<\d+>?1}", name="report_dividend_projection")
     */
    public function projection(
        PositionRepository $positionRepository,
        CalendarRepository $calendarRepository,
        DividendMonthRepository $dividendMonthRepository,
        Referer $referer,
        Projection $projection,
        int $projectionyear
    ): Response {
        if ($projectionyear === 1) {
            $projectionyear = date('Y');
        }
        $referer->set('report_dividend_projection', ['projectionyear' => $projectionyear]);

        $result = $projection->projection($projectionyear, $positionRepository, $calendarRepository, $dividendMonthRepository, self::TAX_DIVIDEND, self::EXCHANGE_RATE);
        return $this->render('report/projection/index.html.twig', array_merge($result, [
            'controller_name' => 'ReportController',
            'year' => $projectionyear,
            'currentYear' => date('Y'),
        ]));
    }

    /**
     * @Route("/yield/{orderBy}", name="report_dividend_yield")
     */
    function yield (
        TickerRepository $tickerRepository,
        PositionRepository $positionRepository,
        string $orderBy = 'ticker',
        Yields $yields
    ): Response {
        $result = $yields->yield($tickerRepository, $positionRepository, $orderBy, self::EXCHANGE_RATE);
        return $this->render('report/yield/index.html.twig', array_merge($result, ['controller_name' => 'ReportController']));
    }

    /**
     * @Route("/export", name="report_export")
     */
    public function export(PositionRepository $positionRepository): Response
    {
        $export = new Export($positionRepository);
        $filename = $export->export($positionRepository);

        $response = new BinaryFileResponse($filename);
        $disposition = HeaderUtils::makeDisposition(
            HeaderUtils::DISPOSITION_ATTACHMENT,
            date('Ymd') . '-export.xlxs'
        );
        $response->headers->set('Content-Disposition', $disposition);

        return $response;
    }
}
