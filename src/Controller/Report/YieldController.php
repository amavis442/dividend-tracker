<?php

declare(strict_types=1);

namespace App\Controller\Report;

use App\Helper\Colors;
use App\Service\Chart\BarChartFactory;
use App\Service\Dividend\YieldsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/{_locale<%app.supported_locales%>}/dashboard/report')]
class YieldController extends AbstractController
{
    public const YIELD_PIE_KEY = 'yeildpie_searchPie';

    #[Route(path: '/yield', name: 'report_dividend_yield')]
    public function index(
        YieldsService $yields,
        BarChartFactory $barChartFactory,
        #[MapQueryParameter]string $sort = 'symbol',
        #[MapQueryParameter]string $sortDirection = 'ASC'
    ): Response {
        $result = $yields->yield($sort, $sortDirection);

        return $this->render(
            'report/yield/index.html.twig',
            array_merge($result, [
                'controller_name' => 'ReportController',
                'chart' => $barChartFactory->create(
                    $result['labels'],
                    $result['data'],
                    'Dividend yield',
                    'Yield',
                    Colors::COLORS,
                    Colors::COLORS
                ),
            ])
        );
    }
}
