<?php

namespace App\Controller;

use App\Repository\DividendTrackerRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route(path: '/dashboard/tracker')]
class DividendTrackerController extends AbstractController
{
    #[Route(path: '/dividend', name: 'dividend_tracker')]
    public function index(DividendTrackerRepository $dividendTrackerRepository, TranslatorInterface $translator): Response
    {
        $data = $dividendTrackerRepository->findAll();
        $labels = [];
        $dividendData = [];
        $principleData = [];

        foreach ($data as $item) {
            $dividendData[] = round($item->getDividend(), 2);
            $principleData[] = round($item->getPrinciple(), 2);
            $labels[] = $item->getSampleDate()->format('d-m-Y');
        }
        $chartData = [['label' => $translator->trans('Expected dividend'), 'data' => $dividendData], ['label' => $translator->trans('Principle'), 'data' => $principleData]];

        return $this->render('dividend_tracker/index.html.twig', [
            'controller_name' => 'DividendController',
            'data' => $chartData,
            'labels' => $labels,
        ]);
    }
}
