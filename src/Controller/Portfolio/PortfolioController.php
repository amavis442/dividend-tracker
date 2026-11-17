<?php

namespace App\Controller\Portfolio;

use App\DataProvider\CorporateActionDataProvider;
use App\DataProvider\TransactionDataProvider;
use App\Decorator\Factory\AdjustedDividendDecoratorFactory;
use App\Decorator\Factory\AdjustedPositionDecoratorFactory;
use App\Form\Model\PortfolioGoal;
use App\Entity\Position;
use App\Form\Model\SearchForm;
use App\Entity\User;
use App\Form\PortfolioGoalType;
use App\Form\SearchFormType;
use App\Helper\Colors;
use App\Repository\PortfolioRepository;
use App\Repository\PositionRepository;
use App\Repository\ResearchRepository;
use App\Repository\TickerRepository;
use App\Service\Chart\BarChartFactory;
use App\Service\Dividend\DividendGrowthService;
use App\Service\Position\PositionDetailService;
use App\Service\Referer;
use App\ViewModel\PortfolioViewModel;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Pagerfanta;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;

/** @psalm-suppress PropertyNotSetInConstructor */
#[Route(path: '/{_locale<%app.supported_locales%>}/dashboard/portfolio')]
class PortfolioController extends AbstractController
{
    public const SESSION_KEY = 'portfoliocontroller_session';
    private const HISTORY_BACKGROUND_COLOR = '#0055ff';
    private const HISTORY_BORDER_COLOR = '#000dff';

    public function __construct(private Stopwatch $stopwatch)
    {
    }

    #[Route(path: '/', name: 'portfolio_index', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        TickerRepository $tickerRepository,
        PortfolioRepository $portfolioRepository,
        PortfolioViewModel $model,
        Referer $referer,
        #[MapQueryParameter] int $page = 1,
        #[MapQueryParameter] string $sortBy = 'fullname',
        #[MapQueryParameter] string $orderBy = 'asc'
    ): Response {
        $referer->clear();
        $referer->set('portfolio_index', [
            'page' => $page,
            'orderBy' => $orderBy,
            'sortBy' => $sortBy,
        ]);

        // This can be an enum
        $orderBy = in_array($orderBy, ['asc', 'desc', 'ASC', 'DESC'])
            ? $orderBy
            : 'asc';

        $pie = null;
        $ticker = null;

        // Search form
        $searchForm = new SearchForm();
        $sessionForm = $request->getSession()->get(self::SESSION_KEY, null);

        if ($sessionForm instanceof SearchForm) {
            if (
                $sessionForm->getTicker() &&
                $sessionForm->getTicker()->getId()
            ) {
                $ticker_id = $sessionForm->getTicker()->getId();
                $ticker = $tickerRepository->find($ticker_id);
                $searchForm->setTicker($ticker);
            }
        }

        $form = $this->createForm(SearchFormType::class, $searchForm, [
            'extra_options' => ['include_all_tickers' => false],
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $ticker = $searchForm->getTicker();
            $request->getSession()->set(self::SESSION_KEY, $searchForm);
        }

        /** @var User $user */
        $user = $this->getUser();
        $portfolio = $portfolioRepository->findForUserOrEmpty($user);

        $this->stopwatch->start('portfoliomodel-getpage');

        // Get the data for the page
        $pager = $model->getPager(
            $portfolio->getInvested() ?? 0.0,
            $page,
            $sortBy,
            $orderBy,
            $ticker,
            $pie
        );

        $referer->set('portfolio_index', [
            'page' => $page,
            'orderBy' => $orderBy,
            'sortBy' => $sortBy,
        ]);

        return $this->render('portfolio/index.html.twig', [
            'autoCompleteForm' => $form,
            'portfolio' => $portfolio,
            'pager' => $pager,
            'thisPage' => $page,
            'orderBy' => $orderBy,
            'sortBy' => $sortBy,
        ]);
    }

    #[Route(path: '/show/{id}', name: 'portfolio_show', methods: ['GET'])]
    public function show(
        Request $request,
        Position $position,
        PortfolioRepository $portfolioRepository,
        PositionDetailService $positionDetail,
        DividendGrowthService $dividendGrowth,
        BarChartFactory $barChartFactory,
        Referer $referer,
    ): Response {
        $ticker = $position->getTicker();
        $recentCalendar = $ticker->getRecentDividendDate();

        $positionDetail->loadDividendData($position);

        $netCashAmount = 0.0;
        $amountPerDate = 0.0;
        $nextDividendExDiv = null;
        $nextDividendPayout = null;
        if ($recentCalendar) {
            $netCashAmount = $positionDetail->getNetCashAmount(
                $ticker,
                $recentCalendar,
                $recentCalendar->getCashAmount()
            );
            $amountPerDate = $position->getAmountPerDate($recentCalendar->getExDividendDate());
            $nextDividendExDiv = $recentCalendar->getExDividendDate();
            $nextDividendPayout = $recentCalendar->getPaymentDate();
        }

        /** @var User $user */
        $user = $this->getUser();
        $portfolio = $portfolioRepository->findForUserOrEmpty($user);
        $forwardDividend = $positionDetail->getForwardDividend($position);
        $growth = $dividendGrowth->getData($ticker);

        $referer->set('portfolio_show', ['id' => $position->getId()]);

        return $this->render('portfolio/show.html.twig', [
            'ticker' => $ticker,
            'growth' => $growth,
            'position' => $position,
            'payments' => $position->getPayments(),
            'dividend' => $positionDetail->getDividendsReceived($ticker),
            'calendars' => $ticker->getCalendars()->slice(0, 30),
            'calendarsCount' => $ticker->getCalendars()->count(),
            'dividendRaises' => $positionDetail->getDividendRaises($ticker),
            'totalInvested' => $portfolio->getInvested(),
            'netYearlyDividend' => $positionDetail->getNetYearlyDividend($ticker),
            'percentageAllocated' => $positionDetail->getPercentageAllocated($position, $portfolio),
            'netCashAmount' => $netCashAmount,
            'amountPerDate' => $amountPerDate,
            'expectedPayout' => $netCashAmount * $amountPerDate,
            'yearlyForwardDividendPayout' => $forwardDividend['yearly'],
            'singleTimeForwarddividendPayout' => $forwardDividend['single'],
            'dividendYield' => $forwardDividend['yield'],
            'nextDividendExDiv' => $nextDividendExDiv,
            'nextDividendPayout' => $nextDividendPayout,
            'indexUrl' => $request->getSession()->get(get_class($this)),
            'chartYield' => $barChartFactory->create(
                $growth['labels'],
                $growth['data'],
                'Dividend yield',
                'Yield',
                Colors::COLORS,
                Colors::COLORS
            ),
            'chartPayout' => $barChartFactory->create(
                $growth['labels'],
                $growth['payout'],
                'Dividend payout',
                'Dividend forward',
                Colors::COLORS,
                Colors::COLORS
            ),
        ]);
    }

    #[
        Route(
            path: '/show/info/{id}',
            name: 'portfolio_show_info',
            methods: ['GET']
        )
    ]
    public function showInfo(
        Position $position,
        PortfolioRepository $portfolioRepository,
        PositionDetailService $positionDetail,
        AdjustedDividendDecoratorFactory $adjustedDividendDecorator,
        AdjustedPositionDecoratorFactory $adjustedPositionDecorator,
    ): Response {
        $ticker = $position->getTicker();
        $recentCalendar = $ticker->getRecentDividendDate();

        $data = $positionDetail->loadDividendData($position);

        $adjustedPositionDecorator->load($data['transactions'], $data['corporateActions']);
        $positionDecorator = $adjustedPositionDecorator->decorate($position);

        $adjustedDividendDecorator->load($data['dividends'], $data['corporateActions']);
        $adjustedDividends = $adjustedDividendDecorator->decorate($ticker)->getAdjustedDividend();

        $netCashAmount = 0.0;
        $amountPerDate = 0.0;
        $nextDividendExDiv = null;
        $nextDividendPayout = null;
        if ($recentCalendar) {
            $adjustedCash = $adjustedDividends[$recentCalendar->getId()]['adjusted'];
            $netCashAmount = $positionDetail->getNetCashAmount($ticker, $recentCalendar, $adjustedCash);

            $cutoffDate = $recentCalendar->getExDividendDate();
            $amountPerDate = $positionDecorator->getAdjustedAmountPerDate($cutoffDate);
            $position->setAdjustedAmount($amountPerDate);
            $position->setAdjustedAveragePrice($positionDecorator->getAdjustedAveragePricePerDate($cutoffDate));

            $nextDividendExDiv = $recentCalendar->getExDividendDate();
            $nextDividendPayout = $recentCalendar->getPaymentDate();
        }

        /** @var User $user */
        $user = $this->getUser();
        $portfolio = $portfolioRepository->findForUserOrEmpty($user);
        $forwardDividend = $positionDetail->getForwardDividend($position);

        return $this->render('portfolio/show/_info.html.twig', [
            'ticker' => $ticker,
            'position' => $position,
            'dividend' => $positionDetail->getDividendsReceived($ticker),
            'percentageAllocated' => $positionDetail->getPercentageAllocated($position, $portfolio),
            'netCashAmount' => $netCashAmount,
            'amountPerDate' => $amountPerDate,
            'expectedPayout' => $netCashAmount * $position->getAdjustedAmount(),
            'yearlyForwardDividendPayout' => $forwardDividend['yearly'],
            'singleTimeForwarddividendPayout' => $forwardDividend['single'],
            'dividendYield' => $forwardDividend['yield'],
            'nextDividendExDiv' => $nextDividendExDiv,
            'nextDividendPayout' => $nextDividendPayout,
        ]);
    }

    #[
        Route(
            path: '/show/position/{id}',
            name: 'portfolio_show_position',
            methods: ['GET']
        )
    ]
    public function showPosition(
        #[MapEntity] Position $position,
        PositionRepository $positionRepository,
        TransactionDataProvider $transactionDataProvider,
        CorporateActionDataProvider $corporateActionDataProvider,
        AdjustedPositionDecoratorFactory $adjustedPositionDecorator,
    ): Response {
        $position = $positionRepository->getForPosition($position);

        $transactions = $transactionDataProvider->load([$position]);
        $corporateActions = $corporateActionDataProvider->load([$position->getTicker()]);

        $adjustedPositionDecorator->load($transactions, $corporateActions);
        $positionDecorator = $adjustedPositionDecorator->decorate($position);

        $position->setAdjustedAmount($positionDecorator->getAdjustedAmount());
        $position->setAdjustedAveragePrice($positionDecorator->getAdjustedAveragePrice());


        return $this->render('portfolio/show/position/_position.html.twig', [
            'position' => $position,
        ]);
    }

    #[
        Route(
            path: '/show/dividendprogression/{id}',
            name: 'portfolio_show_dividend_progression',
            methods: ['GET']
        )
    ]
    public function showDividendProgression(
        Position $position,
        DividendGrowthService $dividendGrowth,
        BarChartFactory $barChartFactory,
        TranslatorInterface $translator,
    ): Response {
        $growth = $dividendGrowth->getData($position->getTicker());
        $dividendHistory = $growth['cashPayout'];
        ksort($dividendHistory);

        return $this->render('portfolio/show/_growth.html.twig', [
            'chartYield' => $barChartFactory->create(
                $growth['labels'],
                $growth['data'],
                $translator->trans('Dividend yield'),
                $translator->trans('Yield'),
                Colors::COLORS,
                Colors::COLORS
            ),
            'chartPayout' => $barChartFactory->create(
                $growth['labels'],
                $growth['payout'],
                $translator->trans('Dividend payout'),
                $translator->trans('Dividend history'),
                Colors::COLORS,
                Colors::COLORS
            ),
            'chartYieldHistory' => $barChartFactory->create(
                array_keys($dividendHistory),
                array_values($dividendHistory),
                $translator->trans('Dividend history'),
                $translator->trans('Yield'),
                self::HISTORY_BACKGROUND_COLOR,
                self::HISTORY_BORDER_COLOR
            ),
        ]);
    }

    #[
        Route(
            path: '/show/research/{id}/{page}',
            name: 'portfolio_show_research',
            methods: ['GET']
        )
    ]
    public function showResearch(
        Position $position,
        ResearchRepository $researchRepository,
        int $page = 1
    ): Response {
        $ticker = $position->getTicker();

        $adapter = new QueryAdapter(
            $researchRepository->getForTickerQueryBuilder(
                $ticker,
                'r.id',
                'DESC'
            )
        );
        $pager = new Pagerfanta($adapter);
        $pager->setMaxPerPage(10);
        $pager->setCurrentPage($page);

        return $this->render('portfolio/show/_research.html.twig', [
            'ticker' => $ticker,
            'pager' => $pager,
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
        /** @var User $user */
        $user = $this->getUser();
        $portfolio = $portfolioRepository->findForUserOrEmpty($user);

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
