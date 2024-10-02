<?php

namespace App\Controller;

use App\Entity\Position;
use App\Repository\PositionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\PaymentRepository;
use Doctrine\ORM\EntityManagerInterface;

#[Route(path: '/{_locale<%app.supported_locales%>}/dashboard/upcomming')]
class UpcommingPositionController extends AbstractController
{
    #[Route(path: '/list', name: 'upcomming_position_index', methods: ['GET'])]
    public function index(
        PositionRepository $positionRepository,
        PaymentRepository $paymentRepository
    ): Response {
        $upcommingDividend = $positionRepository->getUpcommingDividend();

        $numActivePosition = $positionRepository->getTotalPositions();
        $numTickers = $positionRepository->getTotalTickers();
        $profit = $positionRepository->getProfit();
        $totalDividend = $paymentRepository->getTotalDividend();
        $allocated = $positionRepository->getSumAllocated();

        return $this->render('upcomming_position/index.html.twig', [
            'positions' => $upcommingDividend,
            'numActivePosition' => $numActivePosition,
            'numPosition' => $numActivePosition,
            'numTickers' => $numTickers,
            'profit' => $profit,
            'totalDividend' => $totalDividend,
            'allocated' => $allocated,
            'routeName' => 'position_index',
            'searchPath' => 'position_search',
        ]);
    }

    #[Route(path: '/{id}', name: 'upcomming_position_show', methods: ['GET'])]
    public function show(Position $position): Response
    {
        return $this->render('upcomming_position/show.html.twig', [
            'position' => $position,
        ]);
    }

    #[Route(path: '/delete/{id}', name: 'upcomming_position_delete', methods: ['POST', 'DELETE'])]
    public function delete(Request $request, EntityManagerInterface $entityManager, Position $position): Response
    {
        if ($this->isCsrfTokenValid('delete' . $position->getId(), $request->request->get('_token'))) {
            $entityManager->remove($position);
            $entityManager->flush();
        }

        return $this->redirectToRoute('upcomming_position_index');
    }
}
