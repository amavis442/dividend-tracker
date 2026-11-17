<?php

declare(strict_types=1);

namespace App\Service\IncomesShares;

use App\Entity\IncomesSharesDataSet;
use App\Helper\Colors;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

/**
 * Line charts of all IncomesShares datasets over time.
 */
final class IncomesSharesChartFactory
{
    private const TITLE_FONT_SIZE = 24;

    public function __construct(
        private readonly ChartBuilderInterface $chartBuilder,
        private readonly TranslatorInterface $translator,
    ) {
    }

    /**
     * @param IncomesSharesDataSet[] $dataSets
     */
    public function createReturnChart(array $dataSets): Chart
    {
        $series = $this->series($dataSets);
        $colors = Colors::COLORS;

        $datasets = [];
        foreach (
            [
                'Total return' => $series['totalReturn'],
                'Principle' => $series['allocation'],
                'Distributions' => $series['distribution'],
                'Current value' => $series['currentValue'],
            ] as $label => $data
        ) {
            $datasets[] = [
                'label' => $this->translator->trans($label),
                'backgroundColor' => $colors[count($datasets)],
                'borderColor' => $colors,
                'data' => $data,
            ];
        }

        $chart = $this->chartBuilder->createChart(Chart::TYPE_LINE);
        $chart->setData(['labels' => $series['labels'], 'datasets' => $datasets]);
        $chart->setOptions($this->options('Expected dividend'));

        return $chart;
    }

    /**
     * Allocation minus total return per dataset; under zero means selling
     * would not result in a loss.
     *
     * @param IncomesSharesDataSet[] $dataSets
     */
    public function createBreakEvenChart(array $dataSets): Chart
    {
        $series = $this->series($dataSets);

        $chart = $this->chartBuilder->createChart(Chart::TYPE_LINE);
        $chart->setData([
            'labels' => $series['labels'],
            'datasets' => [
                [
                    'label' => $this->translator->trans('Break even (under zero is good)'),
                    'data' => $series['breakEven'],
                ],
            ],
        ]);
        $chart->setOptions($this->options('Break even'));

        return $chart;
    }

    /**
     * @param IncomesSharesDataSet[] $dataSets
     *
     * @return array<string, array<int, float|string>> labels, allocation, totalReturn, distribution,
     *                                               currentValue and breakEven per dataset
     */
    private function series(array $dataSets): array
    {
        $series = array_fill_keys(
            ['labels', 'allocation', 'totalReturn', 'distribution', 'currentValue', 'breakEven'],
            []
        );

        foreach ($dataSets as $dataSet) {
            $allocation = $dataSet->getTotalAllocation();
            $totalReturn = $allocation + $dataSet->getTotalDistribution() + $dataSet->getTotalProfitLoss();

            $series['allocation'][] = round($allocation, 2);
            $series['totalReturn'][] = round($totalReturn, 2);
            $series['distribution'][] = round($dataSet->getTotalDistribution(), 2);
            $series['currentValue'][] = $allocation + $dataSet->getTotalProfitLoss();
            $series['breakEven'][] = $allocation - $totalReturn;
            $series['labels'][] = $dataSet->getCreatedAt()->format('d-m-Y');
        }

        return $series;
    }

    private function options(string $title): array
    {
        return [
            'maintainAspectRatio' => false,
            'responsive' => true,
            'plugins' => [
                'title' => [
                    'display' => true,
                    'text' => $this->translator->trans($title),
                    'font' => [
                        'size' => self::TITLE_FONT_SIZE,
                    ],
                ],
                'legend' => [
                    'position' => 'top',
                ],
            ],
        ];
    }
}
