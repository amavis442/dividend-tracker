<?php

namespace App\Controller\Report;

use App\Repository\PositionRepository;
use App\Service\Dividend\DividendService;
use App\Service\Dividend\YieldsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/{_locale<%app.supported_locales%>}/dashboard/report')]
class YieldController extends AbstractController
{
    public const TAX_DIVIDEND = 0.15; // %
    public const EXCHANGE_RATE = 1.19; // dollar to euro
    public const YIELD_PIE_KEY = 'yeildpie_searchPie';

    #[Route(path: '/yield', name: 'report_dividend_yield')]
    public function index(
        PositionRepository $positionRepository,
        YieldsService $yields,
        DividendService $dividendService,
        #[MapQueryParameter]string $sort = 'symbol',
        #[MapQueryParameter]string $sortDirection = 'ASC'
    ): Response {
        $result = $yields->yield($sort, $sortDirection);

        return $this->render('report/yield/index.html.twig', array_merge($result, ['controller_name' => 'ReportController']));
    }
}
