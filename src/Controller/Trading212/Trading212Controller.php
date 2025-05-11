<?php

namespace App\Controller\Trading212;

use App\Entity\Pie;
use App\Helper\Colors;
use App\Repository\Trading212PieInstrumentRepository;
use App\Repository\Trading212PieMetaDataRepository;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

#[
	Route(
		path: '/{_locale<%app.supported_locales%>}/dashboard/trading212/report'
	)
]
final class Trading212Controller extends AbstractController
{
	#[Route('/', name: 'app_report_trading212_index')]
	public function index(
		Trading212PieMetaDataRepository $trading212PieMetaDataRepository,
		#[MapQueryParameter] $page = 1
	): Response {
		$queryBuilder = $trading212PieMetaDataRepository->all();

		$adapter = new QueryAdapter($queryBuilder);
		$pager = new Pagerfanta($adapter);
		$pager->setMaxPerPage(10);
		$pager->setCurrentPage($page);

		return $this->render('trading212/report/index.html.twig', [
			'title' => 'Trading212Controller',
			'pager' => $pager,
		]);
	}

	#[Route('/graph/{pie}', name: 'app_report_trading212_graph')]
	public function graph(
		Pie $pie,
		Trading212PieMetaDataRepository $trading212PieMetaDataRepository,
		Trading212PieInstrumentRepository $trading212PieInstrumentRepository,
        TranslatorInterface $translator,
        ChartBuilderInterface $chartBuilder,
	): Response {
		$data = $trading212PieMetaDataRepository->findBy(
			['pie' => $pie],
			['createdAt' => 'ASC']
		);

        $metaData = $trading212PieMetaDataRepository->findOneBy(
			['pie' => $pie],
			['createdAt' => 'DESC']
		);
        $instruments = $metaData->getTrading212PieInstruments();

		$labels = [];
		$allocationData = [];
		$valueData = [];

		$colors = Colors::COLORS;

        /**
         * @var \App\Entity\Trading212PieMetaData $item
         */
		foreach ($data as $item) {
			$allocationData[] = round($item->getPriceAvgInvestedValue(), 2);
			$valueData[] = round($item->getPriceAvgValue(), 2);
			$labels[] = $item->getCreatedAt()->format('d-m-Y');
		}
		$chartData = [
			[
				'label' => $translator->trans('Invested'),
				'data' => $allocationData,
			],
			[
				'label' => $translator->trans('Current value'),
				'data' => $valueData,
			],
		];

		$chart = $chartBuilder->createChart(Chart::TYPE_LINE);
		$chart->setData([
			'labels' => $labels,
			'datasets' => [
				[
					'label' => $chartData[0]['label'],
					'backgroundColor' => $colors[0],
					'borderColor' => $colors,
					'data' => $chartData[0]['data'],
				],
				[
					'label' => $chartData[1]['label'],
					'backgroundColor' => $colors[1],
					'borderColor' => $colors,
					'data' => $chartData[1]['data'],
				],
			],
		]);

		$chart->setOptions([
			'maintainAspectRatio' => false,
			'responsive' => true,
			'plugins' => [
				'title' => [
					'display' => true,
					'text' => $translator->trans($pie->getLabel()),
					'font' => [
						'size' => 24,
					],
				],
				'legend' => [
					'position' => 'top',
				],
			],
		]);

        //$trading212PieInstrumentRepository;

		return $this->render('trading212/report/graph.html.twig', [
			'title' => 'Trading212Controller',
            'chart' => $chart,
            'instruments' => $instruments,
		]);
	}
}
