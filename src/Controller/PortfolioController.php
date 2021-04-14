<?php

namespace App\Controller;

use App\Entity\Calendar;
use App\Entity\Position;
use App\Model\PortfolioModel;
use App\Repository\PaymentRepository;
use App\Repository\PieRepository;
use App\Repository\PositionRepository;
use App\Repository\TickerRepository;
use App\Service\DividendGrowth;
use App\Service\DividendService;
use App\Service\Referer;
use App\Service\Summary;
use App\Service\YahooFinanceService;
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
     * @Route("/list/{page<\d+>?1}/{orderBy?fullname}/{sort?asc}", name="portfolio_index", methods={"GET"})
     */
    public function index(
        Request $request,
        PositionRepository $positionRepository,
        PaymentRepository $paymentRepository,
        TickerRepository $tickerRepository,
        PieRepository $pieRepository,
        SessionInterface $session,
        Summary $summary,
        int $page = 1,
        string $orderBy = 'fullname',
        string $sort = 'asc',
        DividendService $dividendService,
        Referer $referer,
        YahooFinanceService $yahooFinanceService,
        PortfolioModel $model
    ): Response {
        if (!in_array($sort, ['asc', 'desc', 'ASC', 'DESC'])) {
            $sort = 'asc';
        }

        $limit = 20;
        $pies = $pieRepository->findLinked();
        $searchCriteria = $session->get(self::SEARCH_KEY, '');
        $pieSelected = $session->get(self::PIE_KEY, null);
        $thisPage = $page;
        [$numActivePosition, $numTickers, $profit, $totalDividend, $allocated] = $summary->getSummary();
        $referer->set('portfolio_index', ['page' => $page, 'orderBy' => $orderBy, 'sort' => $sort]);
        $user = $this->getUser();

        $pageData = $model->getPage(
            $positionRepository,
            $tickerRepository,
            $dividendService,
            $paymentRepository,
            $yahooFinanceService,
            $user->getId(),
            $allocated,
            $page,
            $orderBy,
            $sort,
            $searchCriteria,
            $pieSelected,
        );
        
        $session->set(get_class($this), $request->getRequestUri());

        return $this->render('portfolio/index.html.twig', [
            'portfolioItems'  => $pageData->getPortfolioItems(),
            'cacheTimestamp' => (new DateTime())->setTimestamp($pageData->getCacheTimestamp()),
            'limit' => $limit,
            'maxPages' => $pageData->getMaxPages(),
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
        SessionInterface $session,
        Position $position,
        PositionRepository $positionRepository,
        PaymentRepository $paymentRepository,
        Summary $summary,
        DividendGrowth $dividendGrowth,
        DividendService $dividendService,
        Referer $referer
    ): Response {
        $ticker = $position->getTicker();
        $calendarRecentDividendDate = $ticker->getRecentDividendDate();
        $netCashAmount = 0.0;
        $amountPerDate = 0.0;

        if ($calendarRecentDividendDate) {
            [$exchangeRate, $dividendTax] = $dividendService->getExchangeAndTax($calendarRecentDividendDate);
            $netCashAmount = $calendarRecentDividendDate->getCashAmount() * $exchangeRate * (1 - $dividendTax);
            $amountPerDate = $position->getAmountPerDate($calendarRecentDividendDate->getExDividendDate());
        }

        $position = $positionRepository->getForPosition($position);
        $netYearlyDividend = 0.0;
        $cals = $ticker->getCalendars();
        if (count($cals) > 0) {
            [$exchangeRate, $dividendTax] = $dividendService->getExchangeAndTax($cals[0]);
            $dividendFrequentie = $ticker->getPayoutFrequency();
            $netYearlyDividend = (($dividendFrequentie * $cals[0]->getCashAmount()) * $exchangeRate) * (1 - $dividendTax);
        }
        $payments = $position->getPayments();
        $dividends = $paymentRepository->getSumDividends([$ticker->getId()]);
        $dividend = 0;
        if (!empty($dividends)) {
            $dividend = $dividends[$ticker->getId()];
        }
        $growth = $dividendGrowth->getData($ticker);

        $allocated = $summary->getTotalAllocated();
        $percentageAllocation = 0;

        if ($allocated > 0) {
            $percentageAllocation = ($position->getAllocation() / $allocated) * 100;
        }

        $calendars = $ticker->getCalendars()->slice(0, 5);
        $calendarsCount = $ticker->getCalendars()->count();

        $referer->set('portfolio_show', ['id' => $position->getId()]);
        
        $indexUrl = $session->get(get_class($this));


        return $this->render('portfolio/show.html.twig', [
            'ticker' => $ticker,
            'growth' => $growth,
            'position' => $position,
            'payments' => $payments,
            'dividend' => $dividend,
            'dividendService' => $dividendService,
            'calendars' => $calendars,
            'calendarsCount' => $calendarsCount,
            'totalInvested' => $allocated,
            'netYearlyDividend' => $netYearlyDividend,
            'percentageAllocated' => $percentageAllocation,
            'netCashAmount' => $netCashAmount,
            'amountPerDate' => $amountPerDate,
            'expectedPayout' => $netCashAmount * $amountPerDate,
            'calendarRecentDividendDate' => $calendarRecentDividendDate ?? new Calendar(),
            'indexUrl' => $indexUrl,
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
