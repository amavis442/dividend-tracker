<?php

namespace App\Controller;

use App\Repository\DividendTrackerRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/dashboard/tracker")
 */
class DividendTrackerController extends AbstractController
{
    /**
     * @Route("/dividend", name="dividend_tracker")
     */
    public function index(DividendTrackerRepository $dividendTrackerRepository): Response
    {
        $data = $dividendTrackerRepository->findAll();
        $labels = [];
        $chartData = [];
        foreach ($data as $item) {
            $chartData[] = $item->getDividend();
            $labels[] = $item->getSampleDate()->format('Y-m-d');
        }

        return $this->render('dividend_tracker/index.html.twig', [
            'controller_name' => 'DividendController',
            'data' => $chartData,
            'labels' => $labels
            ]);
    }
}
