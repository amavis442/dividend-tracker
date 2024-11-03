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
	public const YIELD_PIE_KEY = 'yieldpie_searchPie';

	#[Route(path: '/pieyield', name: 'report_dividend_yield_by_pie')]
	public function index(
		Request $request,
		YieldsService $yields,
		ChartBuilderInterface $chartBuilder,
		#[MapQueryParameter] string $sort = 'symbol',
		#[MapQueryParameter] string $sortDirection = 'asc'
	): Response {
		$pie = null;
		$pieSelect = new PieSelect();
		$form = $this->createForm(PieSelectFormType::class, $pieSelect);
		$form->handleRequest($request);
		if ($form->isSubmitted() && $form->isValid()) {
			$pieSelect = $form->getData();
			$request->getSession()->set(self::YIELD_PIE_KEY, $pieSelect);
			if ($pieSelect && $pieSelect->getPie()) {
				$pie = $pieSelect->getPie();
			}
		}

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

		$pieSelected = $request->getSession()->get(self::YIELD_PIE_KEY, null);
		$result = $yields->yield($sort, $sortDirection, $pie);

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
				'controller_name' => 'ReportController',
				//'pies' => $pies,
				'form' => $form,
				'sort' => $sort,
				'sortDirection' => $sortDirection,
				'pieSelected' => $pieSelected,
				'chart' => $chart,
			])
		);
	}
}
