<?php

namespace App\Controller\Report;

use App\Repository\DividendMonthRepository;
use App\Repository\PaymentRepository;
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
        PaymentRepository $paymentRepository
    ): Response {
        
        $data = $paymentRepository->getDividendsPerInterval();

        $labels = '['.implode(',',array_keys($data)).']';
        $accumulative = '[';
        $dividends = '[';
        foreach ($data as $item) {
            $dividends .= ($item['dividend']/100).',';
            $accumulative .= ($item['accumulative']/100).',';
        }
        $dividends .= ']';
        $accumulative .= ']';
        return $this->render('report/dividendmonth/index.html.twig', [
            'data' => $data,
            'labels' => $labels,
            'dividends' => $dividends,
            'accumulative' => $accumulative,
            'controller_name' => 'DividendMonthController',
        ]);
    }
}
