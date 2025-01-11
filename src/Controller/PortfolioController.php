<?php

namespace App\Controller;

use App\Contracts\Service\DividendServiceInterface;
use App\Entity\Calendar;
use App\Entity\Payment;
use App\Entity\Pie;
use App\Entity\Portfolio;
use App\Entity\PortfolioGoal;
use App\Entity\Position;
use App\Entity\SearchForm;
use App\Entity\Ticker;
use App\Entity\Transaction;
use App\Entity\User;
use App\Form\CalendarType;
use App\Form\PaymentType;
use App\Form\PortfolioGoalType;
use App\Form\SearchFormType;
use App\Form\TransactionPieType;
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
use App\Repository\PieRepository;
use App\Repository\PortfolioRepository;
use App\Repository\ResearchRepository;
use App\Repository\TransactionRepository;
use Pagerfanta\Adapter\ArrayAdapter;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Pagerfanta;
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

	#[Route(path: '/', name: 'portfolio_index', methods: ['GET', 'POST'])]
	public function index(
		Request $request,
		TickerRepository $tickerRepository,
		PositionRepository $positionRepository,
		PortfolioRepository $portfolioRepository,
		PortfolioModel $model,
		DividendServiceInterface $dividendService,
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

		$form = $this->createForm(SearchFormType::class, $searchForm, [
			'extra_options' => ['include_all_tickers' => false],
		]);
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

		$this->stopwatch->start('portfoliomodel-getpage');

		$pager = $model->getPager(
			$positionRepository,
			$dividendService,
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
		PositionRepository $positionRepository,
		PaymentRepository $paymentRepository,
		PortfolioRepository $portfolioRepository,
		DividendService $dividendService
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

		if (count($calenders) > 0) {
			$cal = $dividendService->getRegularCalendar($ticker);
			[$exchangeRate, $dividendTax] = $dividendService->getExchangeAndTax(
				$position,
				$cal
			);
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

		$dividends = $paymentRepository->getSumDividends([$ticker->getId()]);
		$dividend = 0;
		if (!empty($dividends) && $ticker->getId() != null) {
			$dividend = $dividends[$ticker->getId()];
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
				($position->getAllocation() ?? 0 / $allocated) * 100;
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
			'dividend' => $dividend,
			'percentageAllocated' => $percentageAllocation,
			'netCashAmount' => $netCashAmount,
			'amountPerDate' => $amountPerDate,
			'expectedPayout' => $netCashAmount * $amountPerDate,
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
		PositionRepository $positionRepository
	): Response {
		$position = $positionRepository->getForPosition($position);

		return $this->render('portfolio/show/position/_position.html.twig', [
			'position' => $position,
		]);
	}

	#[
		Route(
			path: '/show/orders/{id}/{page}',
			name: 'portfolio_show_orders',
			methods: ['GET']
		)
	]
	public function showOrders(
		Position $position,
		TransactionRepository $transactionRepository,
		DividendService $dividendService,
		int $page = 1
	): Response {
		$ticker = $position->getTicker();
		$calenders = $ticker->getCalendars();
		$netYearlyDividend = 0;

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

		$queryBuilder = $transactionRepository->getByPositionQueryBuilder(
			$position
		);
		$adapter = new QueryAdapter($queryBuilder);
		$pager = new Pagerfanta($adapter);
		$pager->setMaxPerPage(10);
		$pager->setCurrentPage($page);

		return $this->render(
			'portfolio/show/transaction/_transactions.html.twig',
			[
				'position' => $position,
				'pager' => $pager,
				'netYearlyDividend' => $netYearlyDividend,
			]
		);
	}

	#[
		Route(
			path: '/create_payment/{position?}',
			name: 'portfolio_create_payment',
			methods: ['GET']
		)
	]
	public function createPayment(
		Request $request,
		EntityManagerInterface $entityManager,
		Position $position
	) {
		$payment = new Payment();
		$payment->setPosition($position);
		$payment->setTicker($position->getTicker());

		$form = $this->createForm(PaymentType::class, $payment);
		$form->handleRequest($request);
		if ($form->isSubmitted() && $form->isValid()) {
			$entityManager->persist($payment);
			$entityManager->flush();

			return $this->redirectToRoute('portfolio_show_payments', [
				'id' => $position->getId(),
				'page' => 1,
			]);
		}

		return $this->render(
			'portfolio/show/payment/_form_payment_create.html.twig',
			[
				'payment' => $payment,
				'position' => $position,
				'ticker' => $position->getTicker(),
				'form' => $form->createView(),
			]
		);
	}

		#[
		Route(
			path: '/edit_payment/{payment}/{page}',
			name: 'portfolio_edit_payment',
			methods: ['GET', 'POST']
		)
	]
	public function editPayment(
		Request $request,
		EntityManagerInterface $entityManager,
		PaymentRepository $paymentRepository,
		Payment $payment,
		int $page = 1
	) {
		$form = $this->createForm(PaymentType::class, $payment);
		$form->handleRequest($request);
		if ($form->isSubmitted() && $form->isValid()) {
			$entityManager->persist($payment);
			$entityManager->flush();

			return $this->redirectToRoute('portfolio_show_payments', [
				'id' => $payment->getPosition()->getId(),
				'page' => 1,
			]);
		}

		$ticker = $payment->getTicker();
		$position = $payment->getPosition();

		$dividends = $paymentRepository->getSumDividends([$ticker->getId()]);
		$dividend = 0;
		if (!empty($dividends) && $ticker->getId() != null) {
			$dividend = $dividends[$ticker->getId()];
		}

		$queryBuilder = $paymentRepository->getForPositionQueryBuilder(
			$position
		);
		$adapter = new QueryAdapter($queryBuilder);
		$pager = new Pagerfanta($adapter);
		$pager->setMaxPerPage(10);
		$pager->setCurrentPage($page);

		return $this->render(
			'portfolio/show/payment/_form_payment_edit.html.twig',
			[
				'edit_payment' => $payment,
				'pager' => $pager,
				'position' => $position,
				'dividend' => $dividend,
				'form' => $form->createView(),
			]
		);
	}

	#[
		Route(
			path: '/delete_payment/{payment}',
			name: 'portfolio_payment_delete',
			methods: ['POST']
		)
	]
	public function deletePayment(
		Request $request,
		EntityManagerInterface $entityManager,
		Payment $payment,
		Position $position
	): Response {
		if (
			$this->isCsrfTokenValid(
				'delete' . $payment->getId(),
				$request->request->get('_token')
			)
		) {
			$entityManager->remove($payment);
			$entityManager->flush();

			$this->addFlash('notice', 'Payment removed.');
		}
		return $this->redirectToRoute('portfolio_show_dividends', [
			'id' => $position->getId(),
			'page' => 1,
		]);
	}

	#[
		Route(
			path: '/show/payments/{id}/{page}',
			name: 'portfolio_show_payments',
			methods: ['GET']
		)
	]
	public function showPayments(
		Position $position,
		PaymentRepository $paymentRepository,
		int $page = 1
	): Response {
		$ticker = $position->getTicker();

		$payments = $position->getPayments();
		$dividends = $paymentRepository->getSumDividends([$ticker->getId()]);
		$dividend = 0;
		if (!empty($dividends) && $ticker->getId() != null) {
			$dividend = $dividends[$ticker->getId()];
		}

		$queryBuilder = $paymentRepository->getForPositionQueryBuilder(
			$position
		);
		$adapter = new QueryAdapter($queryBuilder);
		$pager = new Pagerfanta($adapter);
		$pager->setMaxPerPage(10);
		$pager->setCurrentPage($page);

		return $this->render('portfolio/show/payment/_payments.html.twig', [
			'pager' => $pager,
			'position' => $position,
			'payments' => $payments,
			'dividend' => $dividend,
		]);
	}

	#[
		Route(
			path: '/show/dividends/{id}/{page}',
			name: 'portfolio_show_dividends',
			methods: ['GET']
		)
	]
	public function showDividend(
		Position $position,
		PositionRepository $positionRepository,
		PaymentRepository $paymentRepository,
		int $page = 1
	): Response {
		$ticker = $position->getTicker();
		$calenders = $ticker->getCalendars();

		//$position = $positionRepository->getForPosition($position);
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

		$dividends = $paymentRepository->getSumDividends([$ticker->getId()]);
		$dividend = 0;
		if (!empty($dividends) && $ticker->getId() != null) {
			$dividend = $dividends[$ticker->getId()];
		}
		$calendarsCount = $calenders->count();

		$adapter = new ArrayAdapter($calenders->toArray());
		$pager = new Pagerfanta($adapter);
		$pager->setMaxPerPage(10);
		$pager->setCurrentPage($page);

		return $this->render('portfolio/show/dividend/_dividend.html.twig', [
			'ticker' => $ticker,
			'position' => $position,
			'dividend' => $dividend,
			'pager' => $pager,
			'calendarsCount' => $calendarsCount,
			'dividendRaises' => $dividendRaises,
		]);
	}

	#[
		Route(
			path: '/create_dividend/{ticker?}',
			name: 'portfolio_calendar_new',
			methods: ['GET', 'POST']
		)
	]
	public function createDividend(
		Request $request,
		EntityManagerInterface $entityManager,
		?Ticker $ticker,
		?Position $position
	) {
		$calendar = new Calendar();
		if ($ticker != null) {
			$calendar->setTicker($ticker);
		}
		$form = $this->createForm(CalendarType::class, $calendar);
		$form->handleRequest($request);
		if ($form->isSubmitted() && $form->isValid()) {
			$calendar->setSource(Calendar::SOURCE_MANUEL);
			$entityManager->persist($calendar);
			$entityManager->flush();

			return $this->redirectToRoute('portfolio_show_dividends', [
				'id' => $position->getId(),
				'page' => 1,
			]);
		}

		return $this->render(
			'portfolio/show/dividend/_form_dividend_create.html.twig',
			[
				'calendar' => $calendar,
				'position' => $position,
				'ticker' => $ticker,
				'form' => $form->createView(),
			]
		);
	}

	#[
		Route(
			path: '/delete_dividend/{calendar}/{position}',
			name: 'portfolio_calendar_delete',
			methods: ['POST']
		)
	]
	public function delete(
		Request $request,
		EntityManagerInterface $entityManager,
		Calendar $calendar,
		Position $position
	): Response {
		if (
			$this->isCsrfTokenValid(
				'delete' . $calendar->getId(),
				$request->request->get('_token')
			)
		) {
			if ($calendar->getPayments()->isEmpty()) {
				$entityManager->remove($calendar);
				$entityManager->flush();
			} else {
				$this->addFlash(
					'notice',
					'Can not remove calendar because it has payments linked to it.'
				);
			}
		}

		return $this->redirectToRoute('portfolio_show_dividends', [
			'id' => $position->getId(),
			'page' => 1,
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
		ChartBuilderInterface $chartBuilder
	): Response {
		$ticker = $position->getTicker();
		$growth = $dividendGrowth->getData($ticker);

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

		return $this->render('portfolio/show/_growth.html.twig', [
			'chartYield' => $chartYield,
			'chartPayout' => $chartPayout,
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

	#[
		Route(
			path: '/updatepie/{id}',
			name: 'portfolio_update_pie',
			methods: ['POST', 'GET']
		)
	]
	public function updatePie(
		Request $request,
		Transaction $transaction,
		EntityManagerInterface $entityManager,
		TransactionRepository $transactionRepository
	): Response {
		$transaction = $transactionRepository->find($transaction->getId());

		$form = $this->createForm(TransactionPieType::class, $transaction, [
			'action' => $this->generateUrl('portfolio_update_goal'),
		]);

		$form->handleRequest($request);
		if ($form->isSubmitted() && $form->isValid()) {
			$entityManager->persist($transaction);
			$entityManager->flush();

			return $this->render('portfolio/show/_transaction_pie.html.twig', [
				'transaction' => $transaction,
			]);
		}

		return $this->render('portfolio/show/_form_update_pie.html.twig', [
			'transaction' => $transaction,
			'form' => $form,
			'formTarget' => 'update-pie-' . $transaction->getId(),
		]);
	}
}
