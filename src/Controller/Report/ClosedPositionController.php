<?php

namespace App\Controller\Report;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\BranchRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Repository\PositionRepository;
use App\Repository\PaymentRepository;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use App\Entity\Position;
use App\Form\PositionType;


/**
 * @Route("/dashboard/report")
 */
class ClosedPositionController extends AbstractController
{
    public const SEARCH_KEY = 'closed_position_searchCriteria';

      /**
     * @Route("/closed/{page}/{orderBy}/{sort}", name="report_closed_positions_index", methods={"GET"})
     */
    public function index(
        PositionRepository $positionRepository,
        PaymentRepository $paymentRepository,
        SessionInterface $session,
        int $page = 1,
        string $orderBy = 'buyDate',
        string $sort = 'asc'
    ): Response {
        if (!in_array($orderBy, ['buyDate', 'closeDate','profit', 'ticker'])) {
            $orderBy = 'buyDate';
        }
        if (!in_array($sort, ['asc', 'desc', 'ASC', 'DESC'])) {
            $sort = 'asc';
        }

        $numClosedPosition = $positionRepository->getTotalClosedPositions();
        $numTickers = $positionRepository->getTotalClosedTickers();
        $profit = $positionRepository->getProfit();
        $allocated = $positionRepository->getSumAllocated();
        $totalDividend = $paymentRepository->getTotalDividend();

        $searchCriteria = $session->get(self::SEARCH_KEY, '');
        $items = $positionRepository->getAllClosed($page, 10, $orderBy, $sort, $searchCriteria);
        $limit = 10;
        $maxPages = ceil($items->count() / $limit);
        $thisPage = $page;

        return $this->render('report/position/index.html.twig', [
            'positions' => $items->getIterator(),
            'limit' => $limit,
            'maxPages' => $maxPages,
            'thisPage' => $thisPage,
            'order' => $orderBy,
            'sort' => $sort,
            'numPosition' => $numClosedPosition,
            'numTickers' => $numTickers,
            'profit' => $profit,
            'allocated' => $allocated,
            'totalDividend' => $totalDividend,
            'searchCriteria' => $searchCriteria ?? '',
            'routeName' => 'report_closed_positions_index',
            'searchPath' => 'report_closed_positions_search'
        ]);
    }
    
    /**
     * @Route("/closed/search", name="report_closed_positions_search", methods={"POST"})
     */
    public function search(Request $request, SessionInterface $session): Response
    {
        $searchCriteria = $request->request->get('searchCriteria');
        $session->set(self::SEARCH_KEY, $searchCriteria);

        return $this->redirectToRoute('report_closed_positions_index');
    }
}
