<?php

namespace App\Controller;

use App\Entity\Calendar;
use App\Entity\Position;
use App\Model\PortfolioModel;
use App\Repository\PaymentRepository;
use App\Repository\PieRepository;
use App\Repository\PositionRepository;
use App\Service\DividendGrowthService;
use App\Service\DividendService;
use App\Service\Referer;
use App\Service\SummaryService;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Common\Collections\Collection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Contracts\Cache\ItemInterface;


#[Route(path: '/dashboard/portfolio')]
class PortfolioController extends AbstractController
{
    public const SEARCH_KEY = 'portfolio_searchCriteria';
    public const PIE_KEY = 'portfolio_searchPie';

    public function __construct(
        private Stopwatch $stopwatch,
    ) {}

    #[Route(path: '/list/{page<\d+>?1}/{orderBy?fullname}/{sort?asc}', name: 'portfolio_index', methods: ['GET'])]
    public function index(
        Request $request,
        PositionRepository $positionRepository,
        PaymentRepository $paymentRepository,
        PieRepository $pieRepository,
        SummaryService $summaryService,
        DividendService $dividendService,
        Referer $referer,
        PortfolioModel $model,
        int $page = 1,
        string $orderBy = 'fullname',
        string $sort = 'asc'
    ): Response {

        if (!in_array($sort, ['asc', 'desc', 'ASC', 'DESC'])) {
            $sort = 'asc';
        }
        $limit = 20;
        $pies = $pieRepository->findLinked();
        $searchCriteria = '';
        $pieSelected = null;

        if ($request->hasSession()) {
            $searchCriteria = $request->getSession()->get(self::SEARCH_KEY, '');
            $pieSelected = $request->getSession()->get(self::PIE_KEY, null);
        }

        $thisPage = $page;
        $summary = $summaryService->getSummary();
        $referer->set('portfolio_index', ['page' => $page, 'orderBy' => $orderBy, 'sort' => $sort]);

        $this->stopwatch->start('portfoliomodel-getpage');

        $cache = new FilesystemAdapter(PortfolioModel::CACHE_NAMESPACE);

        $portfolioModel = $cache->get(PortfolioModel::CACHE_KEY . '_' . $page . ($pieSelected ? '_' . $pieSelected : '') . md5($searchCriteria), function (ItemInterface $item) use (
            $model,
            $positionRepository,
            $dividendService,
            $paymentRepository,
            $summary,
            $page,
            $orderBy,
            $sort,
            $searchCriteria,
            $pieSelected
        ): PortfolioModel {

            $item->expiresAfter(3600);

            $portfolioModel = $model->getPage(
                $positionRepository,
                $dividendService,
                $paymentRepository,
                $summary->getAllocated(),
                $page,
                $orderBy,
                $sort,
                $searchCriteria,
                $pieSelected,
            );

            return $portfolioModel;
        });


        $this->stopwatch->stop('portfoliomodel-getpage');

        $request->getSession()->set(get_class($this), $request->getRequestUri());

        return $this->render('portfolio/index.html.twig', [
            'portfolioItems' => $portfolioModel != null ? $portfolioModel->getPortfolioItems() : null,
            'cacheTimestamp' => $portfolioModel != null ? (new DateTime())->setTimestamp($portfolioModel->getCacheTimestamp() ?: 0) : 0,
            'limit' => $limit,
            'maxPages' => $portfolioModel != null ? $portfolioModel->getMaxPages() : 0,
            'thisPage' => $thisPage,
            'order' => $orderBy,
            'sort' => $sort,
            'searchCriteria' => $searchCriteria ?? '',
            'routeName' => 'portfolio_index',
            'searchPath' => 'portfolio_search',
            'piePath' => 'portfolio_pie',
            'pies' => $pies,
            'pieSelected' => $pieSelected ?? 1,
            'numActivePosition' => $summary->getNumActivePosition(),
            'numPosition' => $summary->getNumActivePosition(),
            'numTickers' => $summary->getNumTickers(),
            'profit' => $summary->getProfit(),
            'totalDividend' => $summary->getTotalDividend(),
            'totalInvested' => $summary->getAllocated(),
        ]);
    }

    #[Route(path: '/{id}', name: 'portfolio_show', methods: ['GET'])]
    public function show(
        Request $request,
        Position $position,
        PositionRepository $positionRepository,
        PaymentRepository $paymentRepository,
        SummaryService $summaryService,
        DividendGrowthService $dividendGrowth,
        DividendService $dividendService,
        Referer $referer
    ): Response {
        $ticker = $position->getTicker();
        $calendarRecentDividendDate = $ticker->getRecentDividendDate();
        $netCashAmount = 0.0;
        $amountPerDate = 0.0;
        /**
         * @var Collection<Calendar> $calenders
         */
        $calenders = $ticker->getCalendars();

        $nextDividendExDiv = null;
        $nextDividendPayout = null;

        if ($calendarRecentDividendDate) {
            [$exchangeRate, $dividendTax] = $dividendService->getExchangeAndTax($position, $calendarRecentDividendDate);
            $netCashAmount = $calendarRecentDividendDate->getCashAmount() * $exchangeRate * (1 - $dividendTax);
            $amountPerDate = $position->getAmountPerDate($calendarRecentDividendDate->getExDividendDate());

            $nextDividendExDiv = $calendarRecentDividendDate->getExDividendDate();
            $nextDividendPayout = $nextDividendPayout = $calendarRecentDividendDate->getPaymentDate();
        }

        $position = $positionRepository->getForPosition($position);
        $netYearlyDividend = 0.0;

        if (count($calenders) > 0) {
            $cal = $dividendService->getRegularCalendar($ticker);
            [$exchangeRate, $dividendTax] = $dividendService->getExchangeAndTax($position, $cal);
            $dividendFrequentie = $ticker->getPayoutFrequency();
            $netYearlyDividend = (($dividendFrequentie * $cal->getCashAmount()) * $exchangeRate) * (1 - $dividendTax);
        }
        $dividendRaises = [];

        $reverseCalendars = array_reverse($calenders->toArray(), true);
        // Cals start with latest and descent
        /**
         * @var Calendar $calendar
         */
        foreach ($reverseCalendars as $index => $calendar) {
            $dividendRaises[$index] = 0;
            if ($calendar->getDividendType() === Calendar::REGULAR && stripos($calendar->getDescription(), 'Extra') === false) {
                if (isset($oldCal) && $oldCal->getCashAmount() > 0) {
                    $oldCash = $oldCal->getCashAmount(); // previous
                    $dividendRaises[$index] = (($calendar->getCashAmount() - $oldCash) / $oldCash) * 100;
                }
                $oldCal = $calendar;
            }
        }

        $payments = $position->getPayments();
        $dividends = $paymentRepository->getSumDividends([$ticker->getId()]);
        $dividend = 0;
        if (!empty($dividends)) {
            $dividend = $dividends[$ticker->getId()];
        }
        $growth = $dividendGrowth->getData($ticker);

        $allocated = $summaryService->getTotalAllocated();
        $percentageAllocation = 0;

        if ($allocated > 0) {
            $percentageAllocation = ($position->getAllocation() / $allocated) * 100;
        }

        $calendars = $ticker->getCalendars()->slice(0, 30);
        $calendarsCount = $ticker->getCalendars()->count();


        $yearlyForwardDividendPayout = $position->getTicker()->getPayoutFrequency() * $dividendService->getForwardNetDividend($position);
        $singleTimeForwarddividendPayout = $dividendService->getForwardNetDividend($position);
        $dividendYield = $dividendService->getForwardNetDividendYield($position);

        $referer->set('portfolio_show', ['id' => $position->getId()]);

        $indexUrl = $request->getSession()->get(get_class($this));

        return $this->render('portfolio/show.html.twig', [
            'ticker' => $ticker,
            'growth' => $growth,
            'position' => $position,
            'payments' => $payments,
            'dividend' => $dividend,
            'calendars' => $calendars,
            'calendarsCount' => $calendarsCount,
            'dividendRaises' => $dividendRaises,
            'totalInvested' => $allocated,
            'netYearlyDividend' => $netYearlyDividend,
            'percentageAllocated' => $percentageAllocation,
            'netCashAmount' => $netCashAmount,
            'amountPerDate' => $amountPerDate,
            'expectedPayout' => $netCashAmount * $amountPerDate,
            'yearlyForwardDividendPayout' => $yearlyForwardDividendPayout,
            'singleTimeForwarddividendPayout' => $singleTimeForwarddividendPayout,
            'dividendYield' => $dividendYield,
            'nextDividendExDiv' => $nextDividendExDiv,
            'nextDividendPayout' => $nextDividendPayout,
            'indexUrl' => $indexUrl,
        ]);
    }

    #[Route(path: '/search', name: 'portfolio_search', methods: ['POST'])]
    public function search(Request $request): Response
    {
        $searchCriteria = $request->request->get('searchCriteria');
        $request->getSession()->set(self::SEARCH_KEY, $searchCriteria);

        return $this->redirectToRoute('portfolio_index');
    }

    #[Route(path: '/pie', name: 'portfolio_pie', methods: ['POST'])]
    public function pie(Request $request): Response
    {
        $pie = $request->request->get('pie');
        $request->getSession()->set(self::PIE_KEY, $pie);

        return $this->redirectToRoute('portfolio_index');
    }

    #[Route(path: '/close/{position}', name: 'portfolio_position_close', methods: ['DELETE', 'POST'])]
    public function closePosition(Request $request, EntityManagerInterface $em, Position $position): Response
    {
        if ($this->isCsrfTokenValid('delete' . $position->getId(), $request->request->get('_token'))) {
            $position->setClosed(true);
            $position->setClosedAt((new DateTime()));
            $em->persist($position);
            $em->flush();
        }
        return $this->redirectToRoute('portfolio_index');
    }
}
