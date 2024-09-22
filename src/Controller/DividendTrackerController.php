<?php

namespace App\Controller;

use App\Helper\Colors;
use App\Repository\DividendTrackerRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

#[Route(path: "/dashboard/tracker")]
class DividendTrackerController extends AbstractController
{
    #[Route(path: "/dividend", name: "dividend_tracker")]
    public function index(
        DividendTrackerRepository $dividendTrackerRepository,
        TranslatorInterface $translator,
        ChartBuilderInterface $chartBuilder
    ): Response {
        $data = $dividendTrackerRepository->findAll();
        $labels = [];
        $dividendData = [];
        $principleData = [];

        $colors = Colors::COLORS;

        foreach ($data as $item) {
            $dividendData[] = round($item->getDividend(), 2);
            $principleData[] = round($item->getPrinciple(), 2);
            $labels[] = $item->getSampleDate()->format("d-m-Y");
        }
        $chartData = [
            [
                "label" => $translator->trans("Expected dividend"),
                "data" => $dividendData,
            ],
            [
                "label" => $translator->trans("Principle"),
                "data" => $principleData,
            ],
        ];

        $colors = Colors::COLORS;

        $chart = $chartBuilder->createChart(Chart::TYPE_BAR);

        $chart->setData([
            "labels" => $labels,
            "datasets" => [
                [
                    "label" => $chartData[0]['label'],
                    "backgroundColor" => $colors[0],
                    "borderColor" => $colors,
                    "data" => $chartData[0]['data'],
                ],
                [
                    "label" => $chartData[1]['label'],
                    "backgroundColor" => $colors[1],
                    "borderColor" => $colors,
                    "data" => $chartData[1]['data'],
                ],
            ],
        ]);

        $chart->setOptions([
            "maintainAspectRatio" => false,
            "responsive" => true,
            "plugins" => [
                "title" => [
                    "display" => true,
                    "text" => $translator->trans("Expected dividend"),
                    "font" => [
                        "size" => 24,
                    ],
                ],
                "legend" => [
                    "position" => "top",
                ],
            ],
        ]);

        return $this->render("dividend_tracker/index.html.twig", [
            "controller_name" => "DividendController",
            "chart" => $chart,
        ]);
    }
}
