<?php

namespace App\Controller\Report;

use App\Repository\PieRepository;
use App\Repository\PositionRepository;
use App\Service\DividendService;
use App\Service\YieldsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\RequestStack;

#[Route(path: '/dashboard/report')]
class YieldByPieController extends AbstractController
{
    public const TAX_DIVIDEND = 0.15; // %
    public const EXCHANGE_RATE = 1.19; // dollar to euro
    public const YIELD_PIE_KEY = 'yieldpie_searchPie';

    #[Route(path: '/pieyield/{orderBy}', name: 'report_dividend_yield_by_pie')]
    public function index(
        Request $request,
        PositionRepository $positionRepository,
        PieRepository $pieRepository,
        YieldsService $yields,
        DividendService $dividendService,
        string $orderBy = 'ticker'
    ): Response {

        $pieSelected = $request->getSession()->get(self::YIELD_PIE_KEY, null);
        $result = $yields->yield($positionRepository, $dividendService, $orderBy, $pieSelected);
        $pies = $pieRepository->findLinked();

        return $this->render('report/yield/pie.html.twig', array_merge($result, [
            'controller_name' => 'ReportController', 'pies' => $pies, 'pieSelected' => $pieSelected]));
    }

    #[Route(path: '/pieyieldform', name: 'report_dividend_yield_by_pie_form', methods: ['POST'])]
    public function pieSelect(Request $request): Response
    {
        $pie = $request->request->get('pie');
        $request->getSession()->set(self::YIELD_PIE_KEY, $pie);

        return $this->redirectToRoute('report_dividend_yield_by_pie');
    }
}
