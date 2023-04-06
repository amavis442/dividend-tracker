<?php

namespace App\Controller\Report;

use App\Repository\DividendMonthRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/dashboard/report')]
class DividendMonthController extends AbstractController
{
    #[Route(path: '/dividendmonth', name: 'dividend_month_index')]
    public function index(
        DividendMonthRepository $dividendMonthRepository
    ): Response {

        return $this->render('report/dividendmonth/index.html.twig', [
            'data' => $dividendMonthRepository->getAll(),
            'controller_name' => 'DividendMonthController',
        ]);
    }
}
