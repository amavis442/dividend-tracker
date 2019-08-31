<?php

namespace App\Controller;

use App\Entity\Position;
use App\Form\PositionType;
use App\Repository\PositionRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Repository\TickerRepository;
use DateTime;
use App\Repository\PaymentRepository;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * @Route("/summary")
 */
class SummaryController extends AbstractController
{
    public const SEARCH_KEY = 'position_summary_searchCriteria';
    
     /**
     * @Route("/{page}/{orderBy}/{sort}", name="summary_index", methods={"GET"})
     */
    public function index(
        PositionRepository $positionRepository,
        PaymentRepository $paymentRepository, 
        SessionInterface $session,
        int $page = 1, 
        string $orderBy = 'buyDate', 
        string $sort = 'ASC'
    ): Response
    {
        if (!in_array($orderBy, ['buyDate','profit','ticker'])) {
            $orderBy = 'buyDate';
        }
        if (!in_array($sort, ['asc','desc','ASC','DESC'])) {
            $sort = 'ASC';
        }
        
        $numActivePosition = $positionRepository->getTotalPositions();
        $numTickers = $positionRepository->getTotalTickers();
        $profit = $positionRepository->getProfit();
        $totalDividend = $paymentRepository->getTotalDividend();
        $allocated = $positionRepository->getSumAllocated();

        $searchCriteria = $session->get(self::SEARCH_KEY, '');
        $items = $positionRepository->getSummary($orderBy, $sort, $searchCriteria);
        $limit = 60;
        $thisPage = $page;

        return $this->render('summary/index.html.twig', [
            'positions' => $items,
            'limit' => $limit,
            'thisPage' => $thisPage,
            'order' => $orderBy,
            'sort' => $sort,
            'numActivePosition'=> $numActivePosition,
            'numTickers' => $numTickers,
            'profit' => $profit,
            'totalDividend' => $totalDividend,
            'allocated' => $allocated,
            'searchCriteria' => $searchCriteria ?? '',
            'routeName' => 'summary_index',
            'searchPath' => 'summary_search'
        ]);
    }
    
    /**
     * @Route("/search", name="summary_search", methods={"POST"})
     */
    public function search(Request $request, SessionInterface $session): Response
    {
        $searchCriteria = $request->request->get('searchCriteria');
        $session->set(self::SEARCH_KEY, $searchCriteria);

        return $this->redirectToRoute('summary_index');
    }
}
