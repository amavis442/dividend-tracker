<?php

declare(strict_types=1);

namespace App\Controller\Report;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Helper\Colors;
use App\Repository\PositionRepository;
use App\Entity\User;
use App\Model\AllocationModel;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\UserInterface;
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
        Request $request,
        PositionRepository $positionRepository,
        AllocationModel $allocation,
        TranslatorInterface $translator,
        ChartBuilderInterface $chartBuilder,
        CacheInterface $pool
    ): Response {
        $cacheKey = self::cacheKey('allocation_report', $this->getUser(), $request->getLocale());
        $result = $pool->get($cacheKey, function (
            ItemInterface $item
        ) use (
            $allocation,
            $positionRepository,
            $translator
): array {
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

    /**
     * Report data depends on the user's positions and the locale of the labels,
     * so both are part of the cache key.
     */
    public static function cacheKey(string $report, ?UserInterface $user, string $locale): string
    {
        $userId = $user instanceof User ? (string) $user->getId() : 'anonymous';

        return sprintf('%s.%s.%s', $report, $userId, $locale);
    }
}
