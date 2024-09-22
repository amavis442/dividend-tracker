<?php

namespace App\Controller\Report;

use App\Helper\Colors;
use App\Repository\PaymentRepository;
use App\Service\Payouts;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

#[Route(path: "/dashboard/report")]
class PayoutController extends AbstractController
{
    public const TAX_DIVIDEND = 0.15; // %
    public const EXCHANGE_RATE = 1.19; // dollar to euro
    public const YIELD_PIE_KEY = "yeildpie_searchPie";

    #[Route(path: "/payout", name: "report_payout")]
    public function index(
        PaymentRepository $paymentRepository,
        Payouts $payout,
        UserInterface $user,
        ChartBuilderInterface $chartBuilder
    ): Response {
        $result = $payout->payout($paymentRepository, $user);
        $colors = Colors::COLORS;

        $chart = $chartBuilder->createChart(Chart::TYPE_LINE);

        $chart->setData([
            "labels" => $result["labels"],
            "datasets" => [
                [
                    "label" => "Dividend payout",
                    "backgroundColor" => $colors[0],
                    "borderColor" => $colors[0],
                    "data" => $result["dividends"],
                ],
                [
                    "label" => "Accumulative",
                    "backgroundColor" => $colors[1],
                    "borderColor" => $colors[1],
                    "data" => $result["accumulative"],
                ],
            ],
        ]);

        $chart->setOptions([
            "maintainAspectRatio" => false,
            "responsive" => true,
            "plugins" => [
                "title" => [
                    "display" => true,
                    "text" => "Dividends payout",
                    "font" => [
                        "size" => 24,
                    ],
                ],
                "legend" => [
                    "position" => "top",
                ],
            ],
        ]);

        return $this->render("report/payout/index.html.twig", [
            "controller_name" => "ReportController",
            "chart" => $chart,
        ]);
    }
}
