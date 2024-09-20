<?php

namespace App\Controller;

use App\Entity\Calendar;
use App\Entity\Position;
use App\Entity\PieSelect;
use App\Entity\Pie;
use App\Entity\Ticker;
use App\Entity\SearchForm;
use App\Form\PieSelectFormType;
use App\Model\PortfolioModel;
use App\Repository\PaymentRepository;
use App\Repository\PieRepository;
use App\Repository\PositionRepository;
use App\Repository\TickerRepository;
use App\Service\DividendGrowthService;
use App\Service\DividendService;
use App\Service\Referer;
use App\Service\SummaryService;
use App\Traits\TickerAutocompleteTrait;
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

#[Route(path: "/dashboard/portfolio")]
class PortfolioController extends AbstractController
{
    use TickerAutocompleteTrait;

    public const SEARCH_KEY = "portfolio_searchCriteria";
    public const PIE_KEY = "portfolio_searchPie";
    public const CACHE_SEARCH = "cache_search";
    public const CACHE_PIE = "cache_pie";
    public const CACHE_TICKER = "cache_ticker";

    public function __construct(private Stopwatch $stopwatch)
    {
    }

    #[
        Route(
            path: "/list/{page<\d+>?1}/{orderBy?fullname}/{sort?asc}",
            name: "portfolio_index",
            methods: ["GET", "POST"]
        )
    ]
    public function index(
        Request $request,
        PositionRepository $positionRepository,
        PaymentRepository $paymentRepository,
        TickerRepository $tickerRepository,
        PieRepository $pieRepository,
        SummaryService $summaryService,
        DividendService $dividendService,
        Referer $referer,
        PortfolioModel $model,
        int $page = 1,
        string $orderBy = "fullname",
        string $sort = "asc"
    ): Response {
        if (!in_array($sort, ["asc", "desc", "ASC", "DESC"])) {
            $sort = "asc";
        }
        $limit = 20;
        [$form, $ticker, $pie] = $this->searchTickerAndPie($request, $tickerRepository, $pieRepository, self::CACHE_SEARCH);

        $thisPage = $page;
        $summary = $summaryService->getSummary();
        $referer->set("portfolio_index", [
            "page" => $page,
            "orderBy" => $orderBy,
            "sort" => $sort,
        ]);

        $this->stopwatch->start("portfoliomodel-getpage");

        $cache = new FilesystemAdapter(PortfolioModel::CACHE_NAMESPACE);

        $tickerCacheHash = '_empty';
        if ($ticker && $ticker->getId()) {
            $tickerCacheHash = md5($ticker->getIsin());
        }
        $pieCacheHash = '_empty';
        if ($pie && $pie->getId()) {
            $pieCacheHash = "_" . $pie->getId();
        }
        $portfolioModel = $cache->get(
            PortfolioModel::CACHE_KEY .
                "_" .
                $page .
                $pieCacheHash .
                $tickerCacheHash,
            function (ItemInterface $item) use (
                $model,
                $positionRepository,
                $dividendService,
                $paymentRepository,
                $summary,
                $page,
                $orderBy,
                $sort,
                $ticker,
                $pie
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
                    $ticker,
                    $pie
                );

                return $portfolioModel;
            }
        );

        $this->stopwatch->stop("portfoliomodel-getpage");

        $request
            ->getSession()
            ->set(get_class($this), $request->getRequestUri());

        return $this->render("portfolio/index.html.twig", [
            "portfolioItems" =>
                $portfolioModel != null
                    ? $portfolioModel->getPortfolioItems()
                    : null,
            "cacheTimestamp" =>
                $portfolioModel != null
                    ? (new DateTime())->setTimestamp(
                        $portfolioModel->getCacheTimestamp() ?: 0
                    )
                    : 0,
            "limit" => $limit,
            "maxPages" =>
                $portfolioModel != null ? $portfolioModel->getMaxPages() : 0,
            "thisPage" => $thisPage,
            "order" => $orderBy,
            "sort" => $sort,
            "routeName" => "portfolio_index",
            "numActivePosition" => $summary->getNumActivePosition(),
            "numPosition" => $summary->getNumActivePosition(),
            "numTickers" => $summary->getNumTickers(),
            "profit" => $summary->getProfit(),
            "totalDividend" => $summary->getTotalDividend(),
            "totalInvested" => $summary->getAllocated(),
            "autoCompleteForm" => $form,
            //"pieSelectForm" => $pieSelectForm,
        ]);
    }

    #[Route(path: "/{id}", name: "portfolio_show", methods: ["GET"])]
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
            [$exchangeRate, $dividendTax] = $dividendService->getExchangeAndTax(
                $position,
                $calendarRecentDividendDate
            );
            $netCashAmount =
                $calendarRecentDividendDate->getCashAmount() *
                $exchangeRate *
                (1 - $dividendTax);
            $amountPerDate = $position->getAmountPerDate(
                $calendarRecentDividendDate->getExDividendDate()
            );

            $nextDividendExDiv = $calendarRecentDividendDate->getExDividendDate();
            $nextDividendPayout = $nextDividendPayout = $calendarRecentDividendDate->getPaymentDate();
        }

        $position = $positionRepository->getForPosition($position);
        $netYearlyDividend = 0.0;

        if (count($calenders) > 0) {
            $cal = $dividendService->getRegularCalendar($ticker);
            [$exchangeRate, $dividendTax] = $dividendService->getExchangeAndTax(
                $position,
                $cal
            );
            $dividendFrequentie = $ticker->getPayoutFrequency();
            $netYearlyDividend =
                $dividendFrequentie *
                $cal->getCashAmount() *
                $exchangeRate *
                (1 - $dividendTax);
        }
        $dividendRaises = [];

        $reverseCalendars = array_reverse($calenders->toArray(), true);
        // Cals start with latest and descent
        /**
         * @var Calendar $calendar
         */
        foreach ($reverseCalendars as $index => $calendar) {
            $dividendRaises[$index] = 0;
            if (
                $calendar->getDividendType() === Calendar::REGULAR &&
                stripos($calendar->getDescription() ?? "", "Extra") === false
            ) {
                if (isset($oldCal) && $oldCal->getCashAmount() > 0) {
                    $oldCash = $oldCal->getCashAmount(); // previous
                    $dividendRaises[$index] =
                        (($calendar->getCashAmount() - $oldCash) / $oldCash) *
                        100;
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
            $percentageAllocation =
                ($position->getAllocation() / $allocated) * 100;
        }

        $calendars = $ticker->getCalendars()->slice(0, 30);
        $calendarsCount = $ticker->getCalendars()->count();

        $yearlyForwardDividendPayout =
            $position->getTicker()->getPayoutFrequency() *
            $dividendService->getForwardNetDividend($position);
        $singleTimeForwarddividendPayout = $dividendService->getForwardNetDividend(
            $position
        );
        $dividendYield = $dividendService->getForwardNetDividendYield(
            $position
        );

        $referer->set("portfolio_show", ["id" => $position->getId()]);

        $indexUrl = $request->getSession()->get(get_class($this));

        return $this->render("portfolio/show.html.twig", [
            "ticker" => $ticker,
            "growth" => $growth,
            "position" => $position,
            "payments" => $payments,
            "dividend" => $dividend,
            "calendars" => $calendars,
            "calendarsCount" => $calendarsCount,
            "dividendRaises" => $dividendRaises,
            "totalInvested" => $allocated,
            "netYearlyDividend" => $netYearlyDividend,
            "percentageAllocated" => $percentageAllocation,
            "netCashAmount" => $netCashAmount,
            "amountPerDate" => $amountPerDate,
            "expectedPayout" => $netCashAmount * $amountPerDate,
            "yearlyForwardDividendPayout" => $yearlyForwardDividendPayout,
            "singleTimeForwarddividendPayout" => $singleTimeForwarddividendPayout,
            "dividendYield" => $dividendYield,
            "nextDividendExDiv" => $nextDividendExDiv,
            "nextDividendPayout" => $nextDividendPayout,
            "indexUrl" => $indexUrl,
        ]);
    }

    #[
        Route(
            path: "/close/{position}",
            name: "portfolio_position_close",
            methods: ["DELETE", "POST"]
        )
    ]
    public function closePosition(
        Request $request,
        EntityManagerInterface $em,
        Position $position
    ): Response {
        if (
            $this->isCsrfTokenValid(
                "delete" . $position->getId(),
                $request->request->get("_token")
            )
        ) {
            $position->setClosed(true);
            $position->setClosedAt(new DateTime());
            $em->persist($position);
            $em->flush();
        }
        return $this->redirectToRoute("portfolio_index");
    }
}
