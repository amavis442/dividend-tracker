<?php

namespace App\Controller;

use App\Entity\Ticker;
use App\Repository\PaymentRepository;
use App\Repository\PositionRepository;
use App\Service\Summary;
use App\Repository\TickerRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * @Route("/dashboard/portfolio")
 */
class PortfolioController extends AbstractController
{
    public const SEARCH_KEY = 'portfolio_searchCriteria';

    /**
     * @Route("/list/{page<\d+>?1}", name="portfolio_index", methods={"GET"})
     */
    public function index(
        Summary $summary,
        TickerRepository $tickerRepository, 
        PositionRepository $positionRepository,
        PaymentRepository $paymentRepository,
        SessionInterface $session, 
        int $page = 1, 
        string $orderBy = 'ticker', 
        string $sort = 'asc'
    ): Response
    {
        $searchCriteria = $session->get(self::SEARCH_KEY, '');
        $items = $tickerRepository->getCurrent($page, 10, $orderBy, $sort, $searchCriteria);
        $limit = 10;
        $maxPages = ceil($items->count() / $limit);
        $thisPage = $page;
        
        $iter = $items->getIterator();
        $tickerIds = array_keys($iter->getArrayCopy());
        $dividends = $paymentRepository->getSumDividends($tickerIds);
        //[$numActivePosition, $numTickers, $profit, $totalDividend, $allocated] = $summary->getSummary();
        $posData = $positionRepository->test($tickerIds);

        return $this->render('portfolio/index.html.twig', [
            'tickers' => $items->getIterator(),
            'dividends' => $dividends,
            'positions' => $posData,
            'limit' => $limit,
            'maxPages' => $maxPages,
            'thisPage' => $thisPage,
            'searchCriteria' => $searchCriteria ?? '',
            'routeName' => 'portfolio_index',
            'searchPath' => 'portfolio_search',
            /* 'numActivePosition' => $numActivePosition,
            'numPosition' => $numActivePosition,
            'numTickers' => $numTickers,
            'profit' => $profit,
            'totalDividend' => $totalDividend,
            'allocated' => $allocated, */
        ]);
    }

    /**
     * @Route("/{id}", name="portfolio_show", methods={"GET"})
     */
    public function show(Ticker $ticker): Response
    {
        return $this->render('portfolio/show.html.twig', [
            'ticker' => $ticker,
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
