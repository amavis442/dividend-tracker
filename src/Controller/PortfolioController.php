<?php

namespace App\Controller;

use App\Contracts\Service\DividendServiceInterface;
use App\Entity\Calendar;
use App\Entity\Pie;
use App\Entity\Portfolio;
use App\Entity\PortfolioGoal;
use App\Entity\Position;
use App\Entity\SearchForm;
use App\Entity\User;
use App\Form\PortfolioGoalType;
use App\Form\SearchFormType;
use App\Model\PortfolioModel;
use App\Repository\PaymentRepository;
use App\Repository\PositionRepository;
use App\Repository\TickerRepository;
use App\Service\DividendGrowthService;
use App\Service\DividendService;
use App\Service\Referer;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Stopwatch\Stopwatch;
use App\Helper\Colors;
use App\Repository\PortfolioRepository;
use RuntimeException;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

/** @psalm-suppress PropertyNotSetInConstructor */
#[Route(path: '/{_locale<%app.supported_locales%>}/dashboard/portfolio')]
class PortfolioController extends AbstractController
{
    public const SESSION_KEY = 'portfoliocontroller_session';

    public function __construct(private Stopwatch $stopwatch)
    {
    }

    #[
        Route(
            path: '/list/{page}/{orderBy}/{sort}',
            name: 'portfolio_index',
            methods: ['GET', 'POST']
        )
    ]
    public function index(
        Request $request,
        TickerRepository $tickerRepository,
        PositionRepository $positionRepository,
        PortfolioRepository $portfolioRepository,
        PortfolioModel $model,
        DividendServiceInterface $dividendService,
        Referer $referer,
        #[MapQueryParameter] int $page = 1,
        #[MapQueryParameter] string $orderBy = 'fullname',
        #[MapQueryParameter] string $sort = 'asc'
    ): Response {
        if (!in_array($sort, ['asc', 'desc', 'ASC', 'DESC'])) {
            $sort = 'asc';
        }
//dd($request->getLocale(), $request->getDefaultLocale());
        $pie = null;
        $ticker = null;

        $searchForm = new SearchForm();
        $sessionForm = $request->getSession()->get(self::SESSION_KEY, null);

        if ($sessionForm instanceof SearchForm) {
            if ($sessionForm->getPie() instanceof Pie) {
                $pie = $sessionForm->getPie();
                $searchForm->setPie($pie);
            }

            if (
                $sessionForm->getTicker() &&
                $sessionForm->getTicker()->getId()
            ) {
                $ticker_id = $sessionForm->getTicker()->getId();
                $ticker = $tickerRepository->find($ticker_id);
                $searchForm->setTicker($ticker);
            }
        }

        $form = $this->createForm(
            SearchFormType::class,
            $searchForm,
            ['extra_options' => ['include_all_tickers' => false]]
        );
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $pie = $searchForm->getPie();
            $ticker = $searchForm->getTicker();
            $request->getSession()->set(self::SESSION_KEY, $searchForm);
        }

        /**
         * @var \App\Entity\User $user
         */
        $user = $this->getUser();
        $portfolio = $portfolioRepository->findOneBy([
            'user' => $user->getId(),
        ]);
        if (!$portfolio) {
            $portfolio = new Portfolio(); // do not want to trhow an exception but just use an empty entity
        }

        $referer->set('portfolio_index', [
            'page' => $page,
            'orderBy' => $orderBy,
            'sort' => $sort,
        ]);

        $this->stopwatch->start('portfoliomodel-getpage');

        $pager = $model->getPager(
            $positionRepository,
            $dividendService,
            $portfolio->getInvested() ?? 0.0,
            $page,
            $orderBy,
            $sort,
            $ticker,
            $pie
        );

        $referer->set('portfolio_index', [
            'page' => $page,
            'orderBy' => $orderBy,
            'sort' => $sort,
        ]);

        return $this->render('portfolio/index.html.twig', [
            'autoCompleteForm' => $form,
            'portfolio' => $portfolio,
            'pager' => $pager,
            'thisPage' => $page,
            'orderBy' => $orderBy,
            'sort' => $sort,
            //'ticker' => $ticker != null ? $ticker->getId() : 0,
            //'pie' => $pie != null ? $pie->getId() : 0,
        ]);
    }

    //TODO: REFACTOR!!! This method is to fat
    #[Route(path: '/show/{id}', name: 'portfolio_show', methods: ['GET'])]
    public function show(
        Request $request,
        Position $position,
        PositionRepository $positionRepository,
        PaymentRepository $paymentRepository,
        PortfolioRepository $portfolioRepository,
        DividendGrowthService $dividendGrowth,
        DividendService $dividendService,
        Referer $referer,
        ChartBuilderInterface $chartBuilder
    ): Response {
        $ticker = $position->getTicker();
        $calendarRecentDividendDate = $ticker->getRecentDividendDate();
        $netCashAmount = 0.0;
        $amountPerDate = 0.0;

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
            $nextDividendPayout = $calendarRecentDividendDate->getPaymentDate();
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
                stripos($calendar->getDescription() ?? '', 'Extra') === false
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
        if (!empty($dividends) && $ticker->getId() != null) {
            $dividend = $dividends[$ticker->getId()];
        }
        $growth = $dividendGrowth->getData($ticker);

        /**
         * @var \App\Entity\User $user
         */
        $user = $this->getUser();
        $portfolio = $portfolioRepository->findOneBy([
            'user' => $user->getId(),
        ]);
        if (!$portfolio) {
            $portfolio = new Portfolio(); // do not want to trhow an exception but just use an empty entity
        }

        $allocated = $portfolio->getInvested();
        $percentageAllocation = 0;

        if ($allocated > 0) {
            $percentageAllocation =
                ($position->getAllocation() ?? 0 / $allocated) * 100;
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

        $referer->set('portfolio_show', ['id' => $position->getId()]);

        $indexUrl = $request->getSession()->get(get_class($this));

        $colors = Colors::COLORS;

        $chartPayout = $chartBuilder->createChart(Chart::TYPE_BAR);

        $chartPayout->setData([
            'labels' => $growth['labels'],
            'datasets' => [
                [
                    'label' => 'Dividend payout',
                    'backgroundColor' => $colors,
                    'borderColor' => $colors,
                    'data' => $growth['payout'],
                ],
            ],
        ]);

        $chartPayout->setOptions([
            'maintainAspectRatio' => false,
            'responsive' => true,
            'plugins' => [
                'title' => [
                    'display' => true,
                    'text' => 'Dividend forward',
                    'font' => [
                        'size' => 24,
                    ],
                ],
                'legend' => [
                    'position' => 'top',
                ],
            ],
        ]);

        $chartYield = $chartBuilder->createChart(Chart::TYPE_BAR);

        $chartYield->setData([
            'labels' => $growth['labels'],
            'datasets' => [
                [
                    'label' => 'Dividend yield',
                    'backgroundColor' => $colors,
                    'borderColor' => $colors,
                    'data' => $growth['data'],
                ],
            ],
        ]);

        $chartYield->setOptions([
            'maintainAspectRatio' => false,
            'responsive' => true,
            'plugins' => [
                'title' => [
                    'display' => true,
                    'text' => 'Yield',
                    'font' => [
                        'size' => 24,
                    ],
                ],
                'legend' => [
                    'position' => 'top',
                ],
            ],
        ]);

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
            'chartYield' => $chartYield,
            'chartPayout' => $chartPayout,
        ]);
    }

    #[
        Route(
            path: '/close/{position}',
            name: 'portfolio_position_close',
            methods: ['DELETE', 'POST']
        )
    ]
    public function closePosition(
        Request $request,
        EntityManagerInterface $em,
        Position $position
    ): Response {
        if ($position->getId() == null) {
            throw new RuntimeException('No position to delete');
        }
        $position_id = (int) $position->getId();
        if (
            $this->isCsrfTokenValid(
                'delete' . $position_id,
                (string) $request->request->get('_token')
            )
        ) {
            $position->setClosed(true);
            $position->setClosedAt(new DateTime());
            $em->persist($position);
            $em->flush();
        }
        return $this->redirectToRoute('portfolio_index');
    }

    #[
        Route(
            path: '/updategoal',
            name: 'portfolio_update_goal',
            methods: ['POST', 'GET']
        )
    ]
    public function updateGoal(
        Request $request,
        PortfolioRepository $portfolioRepository,
        EntityManagerInterface $entityManager
    ): Response {
        /**
         * @var User $user
         */
        $user = $this->getUser();
        $portfolio = $portfolioRepository->findOneBy([
            'user' => $user->getId(),
        ]);
        if (!$portfolio) {
            $portfolio = new Portfolio(); // do not want to trhow an exception but just use an empty entity
        }

        $portfolioGoal = new PortfolioGoal();
        $portfolioGoal->setGoal($portfolio->getGoal() ?? 0);
        $form = $this->createForm(PortfolioGoalType::class, $portfolioGoal, [
            'action' => $this->generateUrl('portfolio_update_goal'),
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $newGoal = $portfolioGoal->getGoal();
            $invested = $portfolio->getInvested();

            if ($invested != null && $newGoal != null && $newGoal > 0) {
                $percentage = ($invested / $newGoal) * 100;
                $goalPercentage = round($percentage, 2);
            }
            $portfolio->setGoal($newGoal ?? 0.0);
            $portfolio->setGoalpercentage($goalPercentage ?? 0.0);
            $entityManager->persist($portfolio);
            $entityManager->flush();

            return $this->redirectToRoute(
                'portfolio_index',
                ['target' => '_top'],
                303
            );
        }

        return $this->render('portfolio/_update_goal_form.html.twig', [
            'portfolio' => $portfolio,
            'form' => $form,
            'formTarget' => $request->headers->get('Turbo-Frame', '_top'),
        ]);
    }
}
