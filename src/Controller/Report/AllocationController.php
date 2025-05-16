<?php

namespace App\Controller\Report;

use App\Helper\Colors;
use App\Repository\PositionRepository;
use App\Model\AllocationModel;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

#[Route(path: '/{_locale<%app.supported_locales%>}/dashboard/report')]
class AllocationController extends AbstractController
{
	#[Route(path: '/allocation/sector', name: 'report_allocation_sector')]
	public function index(
		PositionRepository $positionRepository,
		AllocationModel $allocation,
		TranslatorInterface $translator,
		ChartBuilderInterface $chartBuilder,
		CacheInterface $pool
	) {
		$result = $pool->get('allocation_report', function (
			ItemInterface $item
		) use ($allocation, $positionRepository, $translator) {
            $item->expiresAfter(3600);
			return $allocation->allocation($positionRepository, $translator);
		});

		$chart = $chartBuilder->createChart(Chart::TYPE_DOUGHNUT);

		$chart->setData([
			'labels' => $result['labels'],
			'datasets' => [
				[
					'label' => 'Percentage',
					'data' => $result['data'],
				],
			],
		]);

		$chart->setOptions([
			'maintainAspectRatio' => false,
			'responsive' => true,
			'plugins' => [
				'title' => [
					'display' => true,
					'text' => 'Allocation per sector',
					'font' => [
						'size' => 24,
					],
				],
				'legend' => [
					'position' => 'top',
				],
			],
		]);

		return $this->render('report/allocation/index.html.twig', [
			'controller_name' => 'ReportController',
			'chart' => $chart,
		]);
	}
}
