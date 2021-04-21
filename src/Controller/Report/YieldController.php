<?php

namespace App\Controller\Report;

use App\Repository\PositionRepository;
use App\Service\DividendService;
use App\Service\YieldsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/dashboard/report")
 */
class YieldController extends AbstractController
{
    public const TAX_DIVIDEND = 0.15; // %
    public const EXCHANGE_RATE = 1.19; // dollar to euro
    public const YIELD_PIE_KEY = 'yeildpie_searchPie';

    /**
     * @Route("/yield/{orderBy}", name="report_dividend_yield")
     */
    public function index(
        PositionRepository $positionRepository,
        string $orderBy = 'ticker',
        YieldsService $yields,
        DividendService $dividendService
    ): Response {
        $result = $yields->yield($positionRepository, $dividendService, $orderBy);

        return $this->render('report/yield/index.html.twig', array_merge($result, ['controller_name' => 'ReportController']));
    }
}
