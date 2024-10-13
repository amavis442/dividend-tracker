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
		PortfolioRepository $portfolioRepository,
		AllocationModel $allocation,
		ChartBuilderInterface $chartBuilder,
		CacheInterface $pool
	) {
		/**
		 * @var \App\Entity\User $user
		 */
		$user = $this->getUser();
		/**
		 * @var Portfolio|null $portfolio
		 */
		$portfolio = $portfolioRepository->findOneBy([
			'user' => $user->getId(),
		]);
		if (!$portfolio) {
			$portfolio = new Portfolio(); // do not want to trhow an exception but just use an empty entity
		}

		$result = $pool->get('allocation_per_position_report', function (
			ItemInterface $item
		) use ($allocation, $positionRepository, $portfolio) {
			$item->expiresAfter(3600);
			return $allocation->position(
				$positionRepository,
				$portfolio->getInvested()
			);
		});
		//$result = $allocation->position($positionRepository, $portfolio->getInvested());

		$colors = Colors::COLORS;

		$chart = $chartBuilder->createChart(Chart::TYPE_PIE);

		$chart->setData([
			'labels' => $result['labels'],
			'datasets' => [
				[
					'label' => 'Allocation',
					'backgroundColor' => $colors,
					'borderColor' => $colors,
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
			'portfolio' => $portfolio,
			'controller_name' => 'ReportController',
			'chart' => $chart,
		]);
	}
}
