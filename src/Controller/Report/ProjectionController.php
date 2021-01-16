<?php

namespace App\Controller\Report;

use App\Entity\Pie;
use App\Repository\BranchRepository;
use App\Repository\CalendarRepository;
use App\Repository\DividendMonthRepository;
use App\Repository\PaymentRepository;
use App\Repository\PieRepository;
use App\Repository\PositionRepository;
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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @Route("/dashboard/report")
 */
class ProjectionController extends AbstractController
{
    public const TAX_DIVIDEND = 0.15; // %
    public const EXCHANGE_RATE = 1.19; // dollar to euro
    public const YIELD_PIE_KEY = 'yeildpie_searchPie';

    /**
     * @Route("/projection/{projectionyear<\d+>?1}", name="report_dividend_projection")
     */
    public function index(
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
}
