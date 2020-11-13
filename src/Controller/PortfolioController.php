<?php

namespace App\Controller;

use App\Entity\Ticker;
use App\Repository\PaymentRepository;
use App\Repository\PositionRepository;
use App\Service\Summary;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use App\Service\Referer;

/**
 * @Route("/dashboard/portfolio")
 */
class PortfolioController extends AbstractController
{
    public const SEARCH_KEY = 'portfolio_searchCriteria';

    /**
     * @Route("/list/{page<\d+>?1}/{orderBy}/{sort}", name="portfolio_index", methods={"GET"})
     */
    public function index(
        PositionRepository $positionRepository,
        PaymentRepository $paymentRepository,
        SessionInterface $session,
        Summary $summary,
        int $page = 1,
        string $orderBy = 'industry',
        string $sort = 'asc',
        Referer $referer
    ): Response {
        $order = 'i.label';
        if (in_array($orderBy, ['industry'])) {
            $order = 'i.label';
        }
        if (in_array($orderBy, ['ticker', 'fullname'])) {
            $order = 't.' . $orderBy;
        }
        if (!in_array($sort, ['asc', 'desc', 'ASC', 'DESC'])) {
            $sort = 'asc';
        }

        $searchCriteria = $session->get(self::SEARCH_KEY, '');
        $items = $positionRepository->getAll($page, 10, $order, $sort, $searchCriteria);
        $limit = 10;
        $maxPages = ceil($items->count() / $limit);
        $thisPage = $page;
        $iter = $items->getIterator();
        $tickerIds = [];
        foreach ($iter as $position) {
            $id = $position->getTicker()->getId();
            if (!in_array($id, $tickerIds)) {
                $tickerIds[] = $position->getTicker()->getId();
            }
        }
        $dividends = $paymentRepository->getSumDividends($tickerIds);
        [$numActivePosition, $numTickers, $profit, $totalDividend, $allocated] = $summary->getSummary();
        $referer->set('portfolio_index', ['page' => $page, 'orderBy' => $orderBy, 'sort' => $sort]);

        return $this->render('portfolio/index.html.twig', [
            'positions' => $items->getIterator(),
            'dividends' => $dividends,
            'limit' => $limit,
            'maxPages' => $maxPages,
            'thisPage' => $thisPage,
            'order' => $orderBy,
            'sort' => $sort,
            'searchCriteria' => $searchCriteria ?? '',
            'routeName' => 'portfolio_index',
            'searchPath' => 'portfolio_search',
            'numActivePosition' => $numActivePosition,
            'numPosition' => $numActivePosition,
            'numTickers' => $numTickers,
            'profit' => $profit,
            'totalDividend' => $totalDividend,
            'totalInvested' => $allocated,
        ]);
    }

    /**
     * @Route("/{id}", name="portfolio_show", methods={"GET"})
     */
    public function show(
        Ticker $ticker,
        PositionRepository $positionRepository,
        PaymentRepository $paymentRepository,
        Summary $summary,
        Referer $referer
    ): Response {
        $position = $positionRepository->getForTicker($ticker);
        $payments = $paymentRepository->getForTicker($ticker);
        $dividends = $paymentRepository->getSumDividends([$ticker->getId()]);
        $dividend = 0;
        if (!empty($dividends)) {
            $dividend = $dividends[$ticker->getId()] / 100;
        }

        $allocated = $summary->getTotalAllocated();
        $percentageAllocation = 0;
        if ($allocated > 0) {
            $percentageAllocation = ($position->getAllocation() / $allocated);
        }

        $calendar = $ticker->getCalendars();

        $referer->set('portfolio_show',['id' => $ticker->getId()]);

        return $this->render('portfolio/show.html.twig', [
            'ticker' => $ticker,
            'position' => $position,
            'payments' => $payments,
            'dividend' => $dividend,
            'calendars' => $calendar,
            'totalInvested' => $allocated,
            'percentageAllocated' => $percentageAllocation,
        ]);
    }

    /**
     * @Route("/search", name="portfolio_search", methods={"POST"})
     */
    public function search(Request $request, SessionInterface $session): Response
    {
        $searchCriteria = $request->request->get('searchCriteria');
        $session->set(self::SEARCH_KEY, $searchCriteria);

        return $this->redirectToRoute('portfolio_index');
    }
}
