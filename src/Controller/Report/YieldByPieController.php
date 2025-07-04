<?php

namespace App\Controller\Report;

use App\Entity\PieSelect;
use App\Repository\PositionRepository;
use App\Service\DividendService;
use App\Service\YieldsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Form\PieSelectFormType;
use App\Helper\Colors;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

#[Route(path: '/{_locale<%app.supported_locales%>}/dashboard/report')]
class YieldByPieController extends AbstractController
{
	public const TAX_DIVIDEND = 0.15; // %
	public const EXCHANGE_RATE = 1.19; // dollar to euro

	#[Route(path: '/pieyield', name: 'report_dividend_yield_by_pie')]
	public function index(
		YieldsService $yields,
		ChartBuilderInterface $chartBuilder,
		#[MapQueryParameter] string $sort = 'symbol',
		#[MapQueryParameter] string $sortDirection = 'asc'
	): Response {
		$validSorts = ['symbol', 'dividend', 'yield'];
		$sort = in_array($sort, $validSorts) ? $sort : 'symbol';
		$sortDirection = in_array($sortDirection, [
			'asc',
			'ASC',
			'desc',
			'DESC',
		])
			? $sortDirection
			: 'ASC';

		$result = $yields->yield($sort, $sortDirection, null);

		$colors = Colors::COLORS;

		$chart = $chartBuilder->createChart(Chart::TYPE_BAR);
		if (count($result) > 0) {
			$chart->setData([
				'labels' => $result['labels'],
				'datasets' => [
					[
						'label' => 'Dividend yield',
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
						'text' => 'Yield',
						'font' => [
							'size' => 24,
						],
					],
					'legend' => [
						'position' => 'top',
					],
				],
			]);
		}
		return $this->render(
			'report/yield/pie.html.twig',
			array_merge($result, [
				'sort' => $sort,
				'sortDirection' => $sortDirection,
				'chart' => $chart,
			])
		);
	}
}
