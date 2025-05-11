<?php

namespace App\Controller;

use App\Entity\CalcCompound;
use App\Entity\Compound;
use App\Form\CalcCompoundType;
use App\Form\CompoundType;
use App\Model\CompoundCalculator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Helper\Colors;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

#[Route(path: '/{_locale<%app.supported_locales%>}/dashboard/compound')]
class CompoundPredictionController extends AbstractController
{
	#[Route(path: '/prediction', name: 'compound_prediction')]
	public function prediction(
		Request $request,
		CompoundCalculator $compoundCalculator
	): Response {
		$payoutFrequency = 4;
		$startCapital = 0.0;
		$compound = new Compound();
		$compound->setFrequency($payoutFrequency);

		$form = $this->createForm(CompoundType::class, $compound);
		$form->handleRequest($request);

		if ($form->isSubmitted() && $form->isValid()) {
			$data = $compoundCalculator->run($compound);

			return $this->render(
				'compound_prediction/_table-results.html.twig',
				[
					'data' => $data,
					'startCapital' => $startCapital,
					'payoutFrequency' => $payoutFrequency,
				]
			);
		}

		return $this->render('compound_prediction/index.html.twig', [
			'controller_name' => 'CompoundPredictionController',
			'form' => $form,
		]);
	}

	#[Route(path: '/calc-compound', name: 'calc_compound_prediction')]
	public function calcCompound(
		Request $request,
		CompoundCalculator $compoundCalculator,
		TranslatorInterface $translator,
		ChartBuilderInterface $chartBuilder
	): Response {
		$compound = new CalcCompound();

		$form = $this->createForm(CalcCompoundType::class, $compound);
		$form->handleRequest($request);

		if ($form->isSubmitted() && $form->isValid()) {
			$data = $compoundCalculator->calc($compound);
			$chart = $this->graph(
				$data['yearlySummary'],
				$translator,
				$chartBuilder
			);

			return $this->render(
				'compound_prediction/calc_compound/_table-results.html.twig',
				[
					'data' => $data['data'],
					'yearlySummary' => $data['yearlySummary'],
					//'startCapital' => $startCapital,
					'frequency' => $compound->getFrequency(),
					'chart' => $chart,
				]
			);
		}

		return $this->render(
			'compound_prediction/calc_compound/index.html.twig',
			[
				'controller_name' => 'CompoundPredictionController',
				'form' => $form,
			]
		);
	}

	protected function graph(
		$data,
		TranslatorInterface $translator,
		ChartBuilderInterface $chartBuilder
	) {
		$labels = [];
		$capital = [];
		$dividend = [];
		$acumulatedDividend = [];
		$colors = Colors::COLORS;

		foreach ($data as $year => $item) {
			$capital[] = $item['capital'];
			$acumulatedDividend[] = $item['acumulated_dividend'];
			$dividend[] = $item['dividend'];
			$labels[] = $year;
		}
		$chartData = [
			[
				'label' => $translator->trans('Capital'),
				'data' => $capital,
			],
			[
				'label' => $translator->trans('Acumulated dividend'),
				'data' => $acumulatedDividend,
			],
			[
				'label' => $translator->trans('Dividend'),
				'data' => $dividend,
			],
		];

		$chart = $chartBuilder->createChart(Chart::TYPE_BAR);

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
				[
					'label' => $chartData[2]['label'],
					'backgroundColor' => $colors[20],
					'borderColor' => $colors,
					'data' => $chartData[2]['data'],
				],
			],
		]);

		$chart->setOptions([
			'maintainAspectRatio' => false,
			'responsive' => true,
			'plugins' => [
				'title' => [
					'display' => true,
					'text' => $translator->trans('Expected dividend'),
					'font' => [
						'size' => 24,
					],
				],
				'legend' => [
					'position' => 'top',
				],
			],
		]);

		return $chart;
	}
}
