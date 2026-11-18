<?php

declare(strict_types=1);

namespace App\Service\Chart;

use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

/**
 * Builds single-dataset bar charts.
 */
final class BarChartFactory
{
    private const TITLE_FONT_SIZE = 24;

    public function __construct(private readonly ChartBuilderInterface $chartBuilder)
    {
    }

    /**
     * @param array<int|string, string> $labels
     * @param array<int|string, mixed>  $data
     * @param string|string[]           $backgroundColor
     * @param string|string[]           $borderColor
     */
    public function create(
        array $labels,
        array $data,
        string $label,
        string $title,
        string|array $backgroundColor,
        string|array $borderColor,
    ): Chart {
        $chart = $this->chartBuilder->createChart(Chart::TYPE_BAR);

        $chart->setData([
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => $label,
                    'backgroundColor' => $backgroundColor,
                    'borderColor' => $borderColor,
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
                    'text' => $title,
                    'font' => [
                        'size' => self::TITLE_FONT_SIZE,
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
