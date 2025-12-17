<?php
// src/Service/Tradin212/Factory/ChartBuilderFactory.php
namespace App\Service\Trading212\Factory;

use Symfony\Contracts\Translation\TranslatorInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\PaymentRepository;
use Symfony\UX\Chartjs\Model\Chart;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use App\Helper\Colors;
use App\Entity\Pie;
use App\Service\Trading212\CalcStatsService;
use Doctrine\Common\Collections\Collection;


final class ChartBuilderFactory
{
    public function __construct(
        private readonly ChartBuilderInterface $chartBuilder,
        private readonly CalcStatsService $calcStatsService,
        private readonly TranslatorInterface $translator,
    ) {}

    /* ---------- 1. Pie‑instruments chart ----------------------------------- */
    public function buildInstrumentsChart(

        array $pieInstruments
    ): Chart {
        return $this->createPieChart($pieInstruments);
    }

    /* ---------- 2. Main pie chart ------------------------------------------ */
    public function buildMainChart(
        array $data,
        Pie $pie,
    ): Chart {
        // this is the same logic that was in createChart()
        return $this->createChart($data, $pie);
    }

    /* ---------- 3. Break‑even chart ---------------------------------------- */
    public function buildBreakEvenChart(
        array $data
    ): Chart {
        return $this->breakEvenChart($data);
    }

    /* ---------- 4. Yield chart --------------------------------------------- */
    public function buildYieldChart(
        array $data,
        Pie                     $pie
    ): Chart {
        return $this->createYieldChart($data, $pie);
    }

    private function createPieChart(
        array $pieInstruments
    ): Chart {
        $chartInstruments = $this->chartBuilder->createChart(Chart::TYPE_DOUGHNUT);
        $chartInstruments->setData([
            'labels' => $pieInstruments['labels'],
            'datasets' => [
                [
                    'label' => 'Percentage',
                    'data' => $pieInstruments['data'],
                ],
            ],
        ]);
        return $chartInstruments;
    }

    private function createChart(
        array $data,
        Pie $pie
    ): Chart {
        $colors = Colors::COLORS;

        $labels = $data['labels'];
        $allocationData = $data['allocationData'];
        $gained = $data['gained'];
        $totalReturn = $data['totalReturn'];
        $valueData = ['valueData'];

        $colors = Colors::COLORS;

        $chartData = [
            [
                'label' => $this->translator->trans('Invested'),
                'data' => $allocationData,
            ],
            [
                'label' => $this->translator->trans('Current value'),
                'data' => $valueData,
            ],
            [
                'label' => $this->translator->trans('Dividend'),
                'data' => $gained,
            ],
            [
                'label' => $this->translator->trans('Total return'),
                'data' => $totalReturn,
            ],
        ];

        $chart = $this->chartBuilder->createChart(Chart::TYPE_LINE);
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
                    'backgroundColor' => $colors[2],
                    'borderColor' => $colors,
                    'data' => $chartData[2]['data'],
                ],
                [
                    'label' => $chartData[3]['label'],
                    'backgroundColor' => $colors[3],
                    'borderColor' => $colors,
                    'data' => $chartData[3]['data'],
                ],
            ],
        ]);

        $chart->setOptions([
            'maintainAspectRatio' => false,
            'responsive' => true,
            'plugins' => [
                'title' => [
                    'display' => true,
                    'text' => $this->translator->trans($pie->getLabel()),
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



    private function breakEvenChart(
        array $data
    ): Chart {
        $chart = $this->chartBuilder->createChart(Chart::TYPE_LINE);
        $chart->setData([
            'labels' => $data['labels'],
            'datasets' => [
                [
                    'label' => $this->translator->trans('Break even'),
                    'data' => $data['breakEvenData'],
                ],
            ],
        ]);

        $chart->setOptions([
            'maintainAspectRatio' => false,
            'responsive' => true,
            'plugins' => [
                'title' => [
                    'display' => true,
                    'text' => $this->translator->trans(
                        'Break even (under zero is good)'
                    ),
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

    private function createYieldChart(
        array $data,
        Pie $pie,
    ): Chart {
        $yieldData = [];

        foreach ($data as $itemData) {
            $yieldData['labels'][] =
                $itemData['month'] . '-' . $itemData['year'];
            $yield = 0.0;
            if ($itemData['start_invested'] > 0) {
                $deltaGained =
                    $itemData['end_gained'] - $itemData['start_gained'];
                $yield = round(
                    ($deltaGained / $itemData['start_invested']) * 100,
                    2
                );
            }
            $yieldData['data'][] = $yield;
        }

        $chartYield = $this->chartBuilder->createChart(Chart::TYPE_BAR);
        $chartYield->setData([
            'labels' => $yieldData['labels'],
            'datasets' => [
                [
                    'label' => 'Yield',
                    'data' => $yieldData['data'],
                ],
            ],
        ]);

        $chartYield->setOptions([
            'maintainAspectRatio' => false,
            'responsive' => true,
            'plugins' => [
                'title' => [
                    'display' => true,
                    'text' => $this->translator->trans($pie->getLabel()),
                    'font' => [
                        'size' => 24,
                    ],
                ],
                'legend' => [
                    'position' => 'top',
                ],
            ],
        ]);
        return $chartYield;
    }
}
