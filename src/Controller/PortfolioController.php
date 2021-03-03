<?php

namespace App\Controller;

use App\Entity\Position;
use App\Repository\PaymentRepository;
use App\Repository\PieRepository;
use App\Repository\PositionRepository;
use App\Service\DividendGrowth;
use App\Service\Referer;
use App\Service\Summary;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/dashboard/portfolio")
 */
class PortfolioController extends AbstractController
{
    public const SEARCH_KEY = 'portfolio_searchCriteria';
    public const PIE_KEY = 'portfolio_searchPie';

    /**
     * @Route("/list/{page<\d+>?1}/{orderBy?ticker}/{sort?asc}", name="portfolio_index", methods={"GET"})
     */
    public function index(
        PositionRepository $positionRepository,
        PaymentRepository $paymentRepository,
        PieRepository $pieRepository,
        SessionInterface $session,
        Summary $summary,
        int $page = 1,
        string $orderBy = 'ticker',
        string $sort = 'asc',
        Referer $referer
    ): Response {
        $order = 't.ticker';
        if (in_array($orderBy, ['industry'])) {
            $order = 'i.label';
        }
        if (in_array($orderBy, ['ticker', 'fullname'])) {
            $order = 't.' . $orderBy;
        }
        if (!in_array($sort, ['asc', 'desc', 'ASC', 'DESC'])) {
            $sort = 'asc';
        }

        $limit = 20;
        $pies = $pieRepository->findLinked();
        $searchCriteria = $session->get(self::SEARCH_KEY, '');
        $pieSelected = $session->get(self::PIE_KEY, null);
        if ($pieSelected && $pieSelected != '-') {
            $items = $positionRepository->getAll($page, $limit, $order, $sort, $searchCriteria, PositionRepository::OPEN, [$pieSelected]);
        } else {
            $items = $positionRepository->getAll($page, $limit, $order, $sort, $searchCriteria, PositionRepository::OPEN);
        }

        
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
            'piePath' => 'portfolio_pie',
            'pies' => $pies,
            'pieSelected' => $pieSelected ?? 1,
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
        Position $position,
        PositionRepository $positionRepository,
        PaymentRepository $paymentRepository,
        Summary $summary,
        DividendGrowth $dividendGrowth,
        Referer $referer
    ): Response {
        $dividendTax = $this->getParameter('app.dividend.tax');
        $exhangeRate = $this->getParameter('app.dividend.exchangerate');

        $ticker = $position->getTicker();
        $position = $positionRepository->getForPosition($position);
        $netYearlyDividend = 0.0;
        if ($cals = $ticker->getCalendars()) {
            $dividendFrequentie = count($ticker->getDividendMonths());
            $netYearlyDividend = (($dividendFrequentie * $cals[0]->getCashAmount() / 1000) / $exhangeRate) * (1 - $dividendTax);
        }
        $payments = $position->getPayments();
        $dividends = $paymentRepository->getSumDividends([$ticker->getId()]);
        $dividend = 0;
        if (!empty($dividends)) {
            $dividend = $dividends[$ticker->getId()] / 1000;
        }
        $growth = $dividendGrowth->getData($ticker);

        $allocated = $summary->getTotalAllocated();
        $percentageAllocation = 0;
        if ($allocated > 0) {
            $percentageAllocation = (($position->getAllocation() / 10) / $allocated);
        }

        $calendar = $ticker->getCalendars();

        $referer->set('portfolio_show', ['id' => $position->getId()]);

        return $this->render('portfolio/show.html.twig', [
            'ticker' => $ticker,
            'growth' => $growth,
            'position' => $position,
            'payments' => $payments,
            'dividend' => $dividend,
            'calendars' => $calendar,
            'totalInvested' => $allocated,
            'netYearlyDividend' => $netYearlyDividend,
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

    /**
     * @Route("/pie", name="portfolio_pie", methods={"POST"})
     */
    public function pie(Request $request, SessionInterface $session): Response
    {
        $pie = $request->request->get('pie');
        $session->set(self::PIE_KEY, $pie);

        return $this->redirectToRoute('portfolio_index');
    }

    /**
     * @Route("/close/{position}", name="portfolio_position_close", methods={"DELETE"})
     */
    public function closePosition(Request $request, EntityManagerInterface $em, Position $position): Response
    {
        if ($this->isCsrfTokenValid('delete' . $position->getId(), $request->request->get('_token'))) {
            $position->setClosed(1);
            $position->setClosedAt((new DateTime()));
            $em->persist($position);
            $em->flush();
        }
        /*
        $position->setClosed(1);
        $em->persist($position);
        $em->flush();
        */
        return $this->redirectToRoute('portfolio_index');
    }
}
