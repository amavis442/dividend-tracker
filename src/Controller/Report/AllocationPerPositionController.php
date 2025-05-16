<?php

namespace App\Controller\Report;

use App\Entity\Portfolio;
use App\Helper\Colors;
use App\Model\AllocationModel;
use App\Repository\PortfolioRepository;
use App\Repository\PositionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

#[Route(path: '/{_locale<%app.supported_locales%>}/dashboard/report')]
class AllocationPerPositionController extends AbstractController
{
	public const TAX_DIVIDEND = 0.15; // %
	public const EXCHANGE_RATE = 1.19; // dollar to euro
	public const YIELD_PIE_KEY = 'yeildpie_searchPie';

	#[Route(path: '/allocation/position', name: 'report_allocation_position')]
	public function index(
		PositionRepository $positionRepository,
		ChartBuilderInterface $chartBuilder,
		CacheInterface $pool
	) {
		$colors = Colors::COLORS;
		$result = $pool->get('allocation_per_position_report', function (
			ItemInterface $item
		) use ($positionRepository, $colors) {
			$item->expiresAfter(3600);

			$positions = $positionRepository->getOpenPositions();
			$allocated = $positionRepository->getSumAllocated();

			$labels = [];
			$data = [];
			$backgroundColor = [];
			$index = 0;
			foreach ($positions as $position) {
				$labels[] = $position->getTicker()->getFullname();
				$percentage = round(
					($position->getAllocation() / $allocated) * 100,
					2
				);
				$data[] = $percentage;
				$backgroundColor[] = $colors[$index];
				$index++;
			}

			return ['labels' => $labels, 'data' => $data, 'backgroundColor' => $backgroundColor];
		});

		$labels = $result['labels'];
		$data = $result['data'];
		$backgroundColor = $result['backgroundColor'];

		$chart = $chartBuilder->createChart(Chart::TYPE_DOUGHNUT);

		$chart->setData([
			'labels' => $labels,
			'datasets' => [
				[
					'label' => 'Percentage',
					'backgroundColor' => $backgroundColor,
					'data' => $data,
				],
			],
		]);

		$chart->setOptions([
			'maintainAspectRatio' => false,
			'responsive' => true,
			'plugins' => [
				'title' => [
					'display' => true,
					'text' => 'Allocation per position',
					'font' => [
						'size' => 24,
					],
				],
				'legend' => [
					'position' => 'top',
				],
			],
		]);

		return $this->render('report/allocation/position.html.twig', [
			'chart' => $chart,
		]);
	}
}
