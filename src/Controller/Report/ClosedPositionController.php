<?php

namespace App\Controller\Report;

use App\Repository\PaymentRepository;
use App\Repository\PositionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/dashboard/report')]
class ClosedPositionController extends AbstractController
{
    public const SEARCH_KEY = 'closed_position_searchCriteria';

    #[Route(path: '/closed/{page}/{orderBy}/{sort}', name: 'report_closed_positions_index', methods: ['GET'])]
    public function index(
        Request $request,
        PositionRepository $positionRepository,
        PaymentRepository $paymentRepository,
        int $page = 1,
        string $orderBy = 'ticker',
        string $sort = 'asc'
    ): Response {
        if (!in_array($orderBy, ['profit', 'ticker'])) {
            $orderBy = 'ticker';
        }
        if (!in_array($sort, ['asc', 'desc', 'ASC', 'DESC'])) {
            $sort = 'asc';
        }

        $numClosedPosition = $positionRepository->getTotalClosedPositions();
        $numTickers = $positionRepository->getTotalClosedTickers();
        $profit = $positionRepository->getProfit();
        $allocated = $positionRepository->getSumAllocated();
        $totalDividend = $paymentRepository->getTotalDividend();

        $searchCriteria = $request->getSession()->get(self::SEARCH_KEY, '');
        $items = $positionRepository->getAllClosed($page, 10, $sort, $searchCriteria);
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
            'totalInvested' => $allocated,
            'totalDividend' => $totalDividend,
            'searchCriteria' => $searchCriteria ?? '',
            'routeName' => 'report_closed_positions_index',
            'searchPath' => 'report_closed_positions_search',
        ]);
    }

    #[Route(path: '/closed/search', name: 'report_closed_positions_search', methods: ['POST'])]
    public function search(Request $request): Response
    {
        $searchCriteria = $request->request->get('searchCriteria');
        $request->getSession()->set(self::SEARCH_KEY, $searchCriteria);

        return $this->redirectToRoute('report_closed_positions_index');
    }
}
