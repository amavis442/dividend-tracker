<?php

namespace App\Controller\Report;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/dashboard/report")
 */
class PieController extends AbstractController
{
    public const TAX_DIVIDEND = 0.15; // %
    public const EXCHANGE_RATE = 1.19; // dollar to euro
    public const YIELD_PIE_KEY = 'yeildpie_searchPie';

    /**
     * @Route("/pieyieldform", name="report_dividend_yield_by_pie_form", methods={"POST"})
     */
    public function index(Request $request, SessionInterface $session): Response
    {
        $pie = $request->request->get('pie');
        $session->set(self::YIELD_PIE_KEY, $pie);

        return $this->redirectToRoute('report_dividend_yield_by_pie');
    }
}
