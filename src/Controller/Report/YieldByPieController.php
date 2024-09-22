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
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

#[Route(path: "/dashboard/report")]
class YieldByPieController extends AbstractController
{
    public const TAX_DIVIDEND = 0.15; // %
    public const EXCHANGE_RATE = 1.19; // dollar to euro
    public const YIELD_PIE_KEY = "yieldpie_searchPie";

    #[Route(path: "/pieyield/{orderBy}", name: "report_dividend_yield_by_pie")]
    public function index(
        Request $request,
        PositionRepository $positionRepository,
        YieldsService $yields,
        DividendService $dividendService,
        ChartBuilderInterface $chartBuilder,
        string $orderBy = "symbol"
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

        $pieSelected = $request->getSession()->get(self::YIELD_PIE_KEY, null);
        $result = $yields->yield(
            $positionRepository,
            $dividendService,
            $orderBy,
            $pie
        );

        $colors = Colors::COLORS;

        $chart = $chartBuilder->createChart(Chart::TYPE_BAR);

        $chart->setData([
            "labels" => $result["labels"],
            "datasets" => [
                [
                    "label" => "Dividend yield",
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
                    "text" => "Yield",
                    "font" => [
                        "size" => 24,
                    ],
                ],
                "legend" => [
                    "position" => "top",
                ],
            ],
        ]);

        return $this->render(
            "report/yield/pie.html.twig",
            array_merge($result, [
                "controller_name" => "ReportController",
                //'pies' => $pies,
                "form" => $form,
                "pieSelected" => $pieSelected,
                "chart" => $chart,
            ])
        );
    }
}
