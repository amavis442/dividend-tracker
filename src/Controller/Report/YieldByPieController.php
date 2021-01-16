<?php

namespace App\Controller\Report;

use App\Repository\PieRepository;
use App\Repository\PositionRepository;
use App\Service\Yields;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/dashboard/report")
 */
class YieldByPieController extends AbstractController
{
    public const TAX_DIVIDEND = 0.15; // %
    public const EXCHANGE_RATE = 1.19; // dollar to euro
    public const YIELD_PIE_KEY = 'yeildpie_searchPie';

    /**
     * @Route("/pieyield/{orderBy}", name="report_dividend_yield_by_pie")
     */
    public function index(
        string $orderBy = 'ticker',
        PositionRepository $positionRepository,
        PieRepository $pieRepository,
        Yields $yields,
        SessionInterface $session
    ): Response {

        $pieSelected = $session->get(self::YIELD_PIE_KEY, null);
        $result = $yields->yield($positionRepository, $orderBy, self::EXCHANGE_RATE, self::TAX_DIVIDEND, $pieSelected);
        $pies = $pieRepository->findAll();

        return $this->render('report/yield/pie.html.twig', array_merge($result, [
            'controller_name' => 'ReportController', 'pies' => $pies, 'pieSelected' => $pieSelected]));
    }

    /**
     * @Route("/pieyieldform", name="report_dividend_yield_by_pie_form", methods={"POST"})
     */
    public function pieSelect(Request $request, SessionInterface $session): Response
    {
        $pie = $request->request->get('pie');
        $session->set(self::YIELD_PIE_KEY, $pie);

        return $this->redirectToRoute('report_dividend_yield_by_pie');
    }
}
