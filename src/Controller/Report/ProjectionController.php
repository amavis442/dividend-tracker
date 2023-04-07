<?php

namespace App\Controller\Report;

use App\Model\ProjectionModel;
use App\Repository\CalendarRepository;
use App\Repository\DividendMonthRepository;
use App\Repository\PositionRepository;
use App\Service\DividendService;
use App\Service\Referer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/dashboard/report')]
class ProjectionController extends AbstractController
{
    public const TAX_DIVIDEND = 0.15; // %
    public const EXCHANGE_RATE = 1.19; // dollar to euro
    public const YIELD_PIE_KEY = 'yeildpie_searchPie';

    #[Route(path: '/projection/{projectionyear<\d+>?1}', name: 'report_dividend_projection')]
    public function index(
        PositionRepository $positionRepository,
        CalendarRepository $calendarRepository,
        DividendMonthRepository $dividendMonthRepository,
        Referer $referer,
        ProjectionModel $projection,
        DividendService $dividendService,
        int $projectionyear
    ): Response {
        if ($projectionyear === 1) {
            $projectionyear = date('Y');
        }
        $referer->set('report_dividend_projection', ['projectionyear' => $projectionyear]);

        $result = $projection->projection($positionRepository, $dividendMonthRepository, $dividendService, $projectionyear);
        return $this->render('report/projection/index.html.twig', array_merge($result, [
            'controller_name' => 'ReportController',
            'year' => $projectionyear,
            'currentYear' => date('Y'),
        ]));
    }
}
