<?php

namespace App\Controller\Report;

use App\Repository\DividendMonthRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Repository\TickerRepository;

/**
 * @Route("/dashboard/report")
 */
class DividendMonthController extends AbstractController
{
    /**
     * @Route("/dividendmonth", name="dividend_month_index")
     */
    public function index(
        DividendMonthRepository $dividendMonthRepository
    ): Response {
   

        return $this->render('report/dividendmonth/index.html.twig', [
            'data' => $dividendMonthRepository->getAll(),
            'controller_name' => 'DividendMonthController',
        ]);
    }
}
