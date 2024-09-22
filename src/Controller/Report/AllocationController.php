<?php

namespace App\Controller\Report;

use App\Helper\Colors;
use App\Repository\PositionRepository;
use App\Model\AllocationModel;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

#[Route(path: "/dashboard/report")]
class AllocationController extends AbstractController
{
    public const TAX_DIVIDEND = 0.15; // %
    public const EXCHANGE_RATE = 1.19; // dollar to euro
    public const YIELD_PIE_KEY = "yeildpie_searchPie";

    #[Route(path: "/allocation/sector", name: "report_allocation_sector")]
    public function index(
        PositionRepository $positionRepository,
        AllocationModel $allocation,
        TranslatorInterface $translator,
        ChartBuilderInterface $chartBuilder
    ) {
        $result = $allocation->allocation($positionRepository, $translator);

        $colors = Colors::COLORS;

        $chart = $chartBuilder->createChart(Chart::TYPE_PIE);

        $chart->setData([
            "labels" => $result["labels"],
            "datasets" => [
                [
                    "label" => "Allocation",
                    "backgroundColor" => $colors,
                    "borderColor" => $colors,
                    "data" => $result["data"],
                ],
            ],
        ]);

        $chart->setOptions([
            "maintainAspectRatio" => false,
            "responsive" => true,
            "plugins" => [
                "title" => [
                    "display" => true,
                    "text" => "Allocation per sector",
                    "font" => [
                        "size" => 24,
                    ],
                ],
                "legend" => [
                    "position" => "top",
                ],
            ],
        ]);

        return $this->render("report/allocation/index.html.twig", [
            "controller_name" => "ReportController",
            "chart" => $chart,
        ]);
    }
}
