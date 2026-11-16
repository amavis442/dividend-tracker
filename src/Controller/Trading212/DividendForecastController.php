<?php

declare(strict_types=1);

namespace App\Controller\Trading212;

use App\Repository\Trading212PieMetaDataRepository;
use App\Service\Dividend\DividendForecastService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[
    Route(
        path: '/{_locale<%app.supported_locales%>}/dashboard/trading212/report/forecast'
    )
]
final class DividendForecastController extends AbstractController
{
    #[Route('/', name: 'app_report_trading212_dividend_forecast_index')]
    public function index(
        Trading212PieMetaDataRepository $trading212PieMetaDataRepository,
        DividendForecastService $dividendForecastService
    ): Response {
        $data = $trading212PieMetaDataRepository->latest();
        $reportData = [];
        if (count($data) > 0) {
            $snapshotDate = new \DateTime(
                $data[0]->getCreatedAt()->format('Y-m-d H:i:s')
            );

            $result = $dividendForecastService->calculateProjectedPayouts(
                $snapshotDate
            );

            usort(
                $result,
                fn (array $a, array $b): int =>
                [$a['pieLabel'], $a['paymentDate'], $a['ticker']] <=>
                [$b['pieLabel'],$b['paymentDate'], $b['ticker']]
            );

            foreach ($result as $item) {
                $reportData[$item['pieLabel']][$item['paymentDate']->format('Y-m')][] = $item;
            }
        }

        return $this->render(
            'trading212/report/forecast/index.html.twig',
            [
                'title' => 'Dividend forecast',
                'data' => $data,
                'reportData' => $reportData,
            ]
        );
    }
}
