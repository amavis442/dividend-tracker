<?php

namespace App\Controller\Report;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Entity\Portfolio;
use App\Helper\Colors;
use App\Repository\PortfolioRepository;
use App\Repository\PositionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

#[Route(path: '/{_locale<%app.supported_locales%>}/dashboard/report')]
class AllocationPerPositionController extends AbstractController
{
    public const YIELD_PIE_KEY = 'yeildpie_searchPie';

    #[Route(path: '/allocation/position', name: 'report_allocation_position')]
    public function index(
        Request $request,
        PositionRepository $positionRepository,
        ChartBuilderInterface $chartBuilder,
        CacheInterface $pool
    ): Response {
        $colors = Colors::COLORS;
        $cacheKey = AllocationController::cacheKey(
            'allocation_per_position_report',
            $this->getUser(),
            $request->getLocale()
        );
        $result = $pool->get($cacheKey, function (
            ItemInterface $item
        ) use (
            $positionRepository,
            $colors
        ): array {
            $item->expiresAfter(3600);

            $positions = $positionRepository->getOpenPositions();
            $allocated = $positionRepository->getSumAllocated();

            $labels = [];
            $data = [];
            $backgroundColor = [];
            $index = 0;
            foreach ($positions as $position) {
                $labels[] = $position->getTicker()->getFullname();
                $percentage = $allocated > 0
                    ? round(($position->getAllocation() / $allocated) * 100, 2)
                    : 0.0;
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
