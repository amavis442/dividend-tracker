<?php

namespace App\Controller\Portfolio;

use App\DataProvider\CorporateActionDataProvider;
use App\DataProvider\DividendDataProvider;
use App\DataProvider\TransactionDataProvider;
use App\Decorator\Factory\AdjustedDividendDecoratorFactory;
use App\Decorator\Factory\AdjustedPositionDecoratorFactory;

use App\Entity\Calendar;
use App\Entity\Portfolio;
use App\Entity\PortfolioGoal;
use App\Entity\Position;
use App\Entity\SearchForm;
use App\Entity\User;
use App\Form\PortfolioGoalType;
use App\Form\SearchFormType;
use App\Helper\Colors;
use App\Repository\PaymentRepository;
use App\Repository\PortfolioRepository;
use App\Repository\PositionRepository;
use App\Repository\ResearchRepository;
use App\Repository\TickerRepository;
use App\Service\Dividend\DividendGrowthService;
use App\Service\Dividend\DividendServiceInterface;
use App\Service\ExchangeRate\ExchangeAndTaxResolverInterface;
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
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Contracts\Translation\TranslatorInterface;
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

	#[Route(path: '/', name: 'portfolio_index', methods: ['GET', 'POST'])]
	public function index(
		Request $request,
		TickerRepository $tickerRepository,
		PortfolioRepository $portfolioRepository,
		PortfolioViewModel $model,
		Referer $referer,
		#[MapQueryParameter] int $page = 1,
		#[MapQueryParameter] string $sort = 'fullname',
		#[MapQueryParameter] string $orderBy = 'asc'
	): Response {
		$referer->clear();
		$referer->set('portfolio_index', [
			'page' => $page,
			'orderBy' => $orderBy,
			'sort' => $sort,
		]);

		$orderBy = in_array($orderBy, ['asc', 'desc', 'ASC', 'DESC'])
			? $orderBy
			: 'asc';

		$pie = null;
		$ticker = null;

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

		$this->stopwatch->start('portfoliomodel-getpage');

		$pager = $model->getPager(
			$portfolio->getInvested() ?? 0.0,
			$page,
			$sort,
			$orderBy,
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
		]);
	}

	//TODO: REFACTOR!!! This method is to fat
	#[Route(path: '/show/{id}', name: 'portfolio_show', methods: ['GET'])]
	public function show(
		Request $request,
		Position $position,
		PaymentRepository $paymentRepository,
		PortfolioRepository $portfolioRepository,
		DividendGrowthService $dividendGrowth,
		DividendServiceInterface $dividendService,
		ExchangeAndTaxResolverInterface $exchangeAndTaxResolver,
		TransactionDataProvider $transactionDataProvider,
		CorporateActionDataProvider $corporateActionDataProvider,
		DividendDataProvider $dividendDataProvider,
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

		$transactions = $transactionDataProvider->load(
			[$position]
		);
		$actions = $corporateActionDataProvider->load(
			[$ticker]
		);

		$dividends = $dividendDataProvider->load([$ticker]);

		$dividendService->load(
			transactions: $transactions,
			corporateActions: $actions,
			dividends: $dividends
		);


		if ($calendarRecentDividendDate) {
			$exchangeTaxDto = $exchangeAndTaxResolver->resolve($position->getTicker(), $calendarRecentDividendDate);
			$exchangeRate = $exchangeTaxDto->exchangeRate;
			$dividendTax =$exchangeTaxDto->taxAmount;

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

		//$position = $positionRepository->getForPosition($position);
		$netYearlyDividend = 0.0;

		if (count($calenders) > 0) {
			$cal = $dividendService->getRegularCalendar($ticker);

			$exchangeTaxDto = $exchangeAndTaxResolver->resolve($ticker, $cal);
			$exchangeRate = $exchangeTaxDto->exchangeRate;
			$dividendTax =$exchangeTaxDto->taxAmount;

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
			$portfolio = new Portfolio(); // do not want to throw an exception, but just use an empty entity
		}

		$allocated = $portfolio->getInvested();
		$percentageAllocation = 0;

		if ($allocated > 0) {
			$percentageAllocation =
				($position->getAllocation() / $allocated) * 100;
		}

		$calendars = $ticker->getCalendars()->slice(0, 30);
		$calendarsCount = $ticker->getCalendars()->count();

		$yearlyForwardDividendPayout =
			$position->getTicker()->getPayoutFrequency() *
			$dividendService->getForwardNetDividend(
				$position->getTicker(),
				$position->getAmount()
			);
		$singleTimeForwarddividendPayout = $dividendService->getForwardNetDividend(
			$position->getTicker(),
			$position->getAmount()
		);
		$dividendYield = $dividendService->getForwardNetDividendYield(
			$position,
			$position->getTicker(),
			$position->getAmount(),
			$position->getAllocation()
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
			path: '/show/info/{id}',
			name: 'portfolio_show_info',
			methods: ['GET']
		)
	]
	public function showInfo(
		Position $position,
		PaymentRepository $paymentRepository,
		PortfolioRepository $portfolioRepository,
		DividendServiceInterface $dividendService,
		ExchangeAndTaxResolverInterface $exchangeAndTaxResolver,
		AdjustedDividendDecoratorFactory $adjustedDividendDecorator,
		TransactionDataProvider $transactionDataProvider,
		CorporateActionDataProvider $corporateActionDataProvider,
		DividendDataProvider $dividendDataProvider,
		AdjustedPositionDecoratorFactory $adjustedPositionDecorator,
	): Response {
		$ticker = $position->getTicker();
		$calendarRecentDividendDate = $ticker->getRecentDividendDate();
		$netCashAmount = 0.0;
		$amountPerDate = 0.0;

		$calenders = $ticker->getCalendars();

		$nextDividendExDiv = null;
		$nextDividendPayout = null;

		$transactions = $transactionDataProvider->load([$position]);
		$actions = $corporateActionDataProvider->load([$ticker]);
		$dividends = $dividendDataProvider->load([$ticker]);

		$dividendService->load(transactions: $transactions, corporateActions: $actions, dividends: $dividends);

		$adjustedPositionDecorator->load($transactions, $actions);
		$positionDecorator = $adjustedPositionDecorator->decorate($position);

		$adjustedDividendDecorator->load($dividends, $actions);

		$dividendDecorator = $adjustedDividendDecorator->decorate($position->getTicker());
		$adjustedDividends = $dividendDecorator->getAdjustedDividend();

		if ($calendarRecentDividendDate) {
			$exchangeTaxDto = $exchangeAndTaxResolver->resolve($ticker, $calendarRecentDividendDate);
			$exchangeRate = $exchangeTaxDto->exchangeRate;
			$dividendTax =$exchangeTaxDto->taxAmount;

			$adjustedDividend = $adjustedDividends[$calendarRecentDividendDate->getId()];

			$netCashAmount =
				$adjustedDividend['adjusted'] *
				$exchangeRate *
				(1 - $dividendTax);
			/* $amountPerDate = $position->getAmountPerDate(
				$calendarRecentDividendDate->getExDividendDate()
			); */
			$cutoffDate = $calendarRecentDividendDate->getExDividendDate();
			$amountPerDate =  $positionDecorator->getAdjustedAmountPerDate($cutoffDate);
			$position->setAdjustedAmount($amountPerDate);
			$adjustedAvgPrice = $positionDecorator->getAdjustedAveragePricePerDate($cutoffDate);
			$position->setAdjustedAveragePrice($adjustedAvgPrice);

			$nextDividendExDiv = $calendarRecentDividendDate->getExDividendDate();
			$nextDividendPayout = $calendarRecentDividendDate->getPaymentDate();
		}

		if (count($calenders) > 0) {
			$cal = $dividendService->getRegularCalendar($ticker);
			$exchangeTaxDto = $exchangeAndTaxResolver->resolve($ticker, $cal);
			$exchangeRate = $exchangeTaxDto->exchangeRate;
			$dividendTax =$exchangeTaxDto->taxAmount;
		}
		$dividendRaises = [];

		$reverseCalendars = array_reverse($calenders->toArray(), true);

		/**
		 * Calculates the dividend percentage increases from start with latest and descent
		 *
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

		$dividendsReceivedPerTicker = $paymentRepository->getSumDividends([$ticker->getId()]);
		$dividendsReceived = 0.0;
		if (!empty($dividendsReceivedPerTicker) && $ticker->getId() != null) {
			$dividendsReceived = $dividendsReceivedPerTicker[$ticker->getId()];
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

		$allocated = $portfolio->getInvested();
		$percentageAllocation = 0;

		if ($allocated > 0) {
			$percentageAllocation =
				($position->getAllocation() / $allocated) * 100;
		}

		$yearlyForwardDividendPayout =
			$position->getTicker()->getPayoutFrequency() *
			$dividendService->getForwardNetDividend(
				$position->getTicker(),
				$position->getAmount()
			);
		$singleTimeForwarddividendPayout = $dividendService->getForwardNetDividend(
			$position->getTicker(),
			$position->getAmount()
		);
		$dividendYield = $dividendService->getForwardNetDividendYield(
			$position,
			$position->getTicker(),
			$position->getAmount(),
			$position->getAllocation()
		);

		return $this->render('portfolio/show/_info.html.twig', [
			'ticker' => $ticker,
			'position' => $position,
			'dividend' => $dividendsReceived,
			'percentageAllocated' => $percentageAllocation,
			'netCashAmount' => $netCashAmount,
			'amountPerDate' => $amountPerDate,
			'expectedPayout' => $netCashAmount * $position->getAdjustedAmount(),
			'yearlyForwardDividendPayout' => $yearlyForwardDividendPayout,
			'singleTimeForwarddividendPayout' => $singleTimeForwarddividendPayout,
			'dividendYield' => $dividendYield,
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
		Position $position,
		PositionRepository $positionRepository,
		TransactionDataProvider $transactionDataProvider,
		CorporateActionDataProvider $corporateActionDataProvider,
		AdjustedPositionDecoratorFactory $adjustedPositionDecorator,

	): Response {
		$position = $positionRepository->getForPosition($position);

		$transactions = $transactionDataProvider->load([$position]);
		$corporateActions = $corporateActionDataProvider->load([$position->getTicker()]);

		$adjustedPositionDecorator->load($transactions,$corporateActions);
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
		ChartBuilderInterface $chartBuilder,
		TranslatorInterface $translator,
	): Response {
		$ticker = $position->getTicker();
		$growth = $dividendGrowth->getData($ticker);
		$dividendHistory = $growth['cashPayout'];

		$colors = Colors::COLORS;

		$chartPayout = $chartBuilder->createChart(Chart::TYPE_BAR);

		$chartPayout->setData([
			'labels' => $growth['labels'],
			'datasets' => [
				[
					'label' => $translator->trans('Dividend payout'),
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
					'text' => $translator->trans('Dividend history'),
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
					'label' => $translator->trans('Dividend yield'),
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
					'text' => $translator->trans('Yield'),
					'font' => [
						'size' => 24,
					],
				],
				'legend' => [
					'position' => 'top',
				],
			],
		]);

		$chartYieldHistory = $chartBuilder->createChart(Chart::TYPE_BAR);

		ksort($dividendHistory);
		$chartYieldHistory->setData([
			'labels' => array_keys($dividendHistory),
			'datasets' => [
				[
					'label' => $translator->trans('Dividend history'),
					'backgroundColor' => '#0055ff',
					'borderColor' => '#000dff',
					'data' => array_values($dividendHistory),
				],
			],
		]);

		$chartYieldHistory->setOptions([
			'maintainAspectRatio' => false,
			'responsive' => true,
			'plugins' => [
				'title' => [
					'display' => true,
					'text' => $translator->trans('Yield'),
					'font' => [
						'size' => 24,
					],
				],
				'legend' => [
					'position' => 'top',
				],
			],
		]);

		return $this->render('portfolio/show/_growth.html.twig', [
			'chartYield' => $chartYield,
			'chartPayout' => $chartPayout,
			'chartYieldHistory' =>$chartYieldHistory,
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
