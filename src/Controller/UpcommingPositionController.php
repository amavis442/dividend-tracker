<?php

namespace App\Controller;

use App\Entity\Position;
use App\Form\PositionType;
use App\Repository\PositionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\TickerRepository;
use DateTime;
use App\Repository\PaymentRepository;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * @Route("/dashboard/upcomming")
 */
class UpcommingPositionController extends AbstractController
{
    /**
     * @Route("/list", name="upcomming_position_index", methods={"GET"})
     */
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

    /**
     * @Route("/{id}", name="upcomming_position_show", methods={"GET"})
     */
    public function show(Position $position): Response
    {
        return $this->render('upcomming_position/show.html.twig', [
            'position' => $position,
        ]);
    }

    /**
     * @Route("/{id}", name="upcomming_position_delete", methods={"DELETE"})
     */
    public function delete(Request $request, Position $position): Response
    {
        if ($this->isCsrfTokenValid('delete' . $position->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($position);
            $entityManager->flush();
        }

        return $this->redirectToRoute('upcomming_position_index');
    }
}
