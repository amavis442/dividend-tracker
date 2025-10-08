<?php

namespace App\Controller\Trading212;

use App\Repository\Trading212PieMetaDataRepository;
use App\Service\Dividend\DividendForecastService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;


#[
	Route(
		path: '/{_locale<%app.supported_locales%>}/dashboard/trading212/report/foreacast'
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

			foreach ($result as $item) {
				$reportData[$item['pieLabel']][$item['paymentDate']->format('Y-m')][$item['paymentDate']->format('Y-m-d')][
					$item['ticker']
				][] = $item;
			}
			foreach ($reportData as $pie => $item) {
				ksort($reportData[$pie]);
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
