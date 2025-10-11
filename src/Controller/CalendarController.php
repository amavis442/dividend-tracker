<?php

namespace App\Controller;

use App\DataProvider\CorporateActionDataProvider;
use App\DataProvider\DividendDataProvider;
use App\DataProvider\TransactionDataProvider;
use App\Entity\Calendar;
use App\Entity\DateSelect;
use App\Entity\Ticker;
use App\Entity\TickerAutocomplete;
use App\Form\CalendarDividendType;
use App\Form\CalendarType;
use App\Form\TickerAutocompleteType;
use App\Repository\DividendCalendarRepository;
use App\Repository\TickerRepository;
use App\Repository\TransactionRepository;
use App\Repository\PositionRepository;
use App\Service\Dividend\DividendServiceInterface;
use App\Service\Dividend\DividendTaxRateResolverInterface;
use App\Service\ExchangeRate\DividendExchangeRateResolverInterface;
use App\Service\Referer;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\Transaction\TransactionAdjuster;
use App\Service\Dividend\DividendAdjuster;
use App\Service\Dividend\DividendCalendarService;

#[Route(path: '/{_locale<%app.supported_locales%>}/dashboard/calendar')]
class CalendarController extends AbstractController
{
	public const SESSION_KEY = 'calendar_searchCriteria';

	#[Route(path: '/', name: 'calendar_index', methods: ['GET', 'POST'])]
	public function index(
		Request $request,
		DividendCalendarRepository $calendarRepository,
		TickerRepository $tickerRepository,
		Referer $referer,
		#[MapQueryParameter] int $page = 1,
		#[MapQueryParameter] string $sort = 'createdAt',
		#[MapQueryParameter] string $orderBy = 'DESC'
	): Response {
		$referer->clear();
		$referer->set('calendar_index', [
			'page' => $page,
			'orderBy' => $orderBy,
			'sort' => $sort,
		]);

		$sort = in_array($sort, [
			'paymentDate',
			'symbol',
			'exDividendDate',
			'createdAt',
		])
			? $sort
			: 'paymentDate';

		$orderBy = in_array($orderBy, ['asc', 'desc', 'ASC', 'DESC'])
			? $orderBy
			: 'DESC';

		$tickerAutoComplete = new TickerAutocomplete();
		$ticker = null;

		$tickerAutoCompleteCache = $request
			->getSession()
			->get(self::SESSION_KEY, null);

		if ($tickerAutoCompleteCache instanceof TickerAutocomplete) {
			// We need a mapped entity else symfony will complain
			// This works, but i do not know if it is the best solution
			if (
				$tickerAutoCompleteCache->getTicker() &&
				$tickerAutoCompleteCache->getTicker()->getId()
			) {
				$ticker = $tickerRepository->find(
					$tickerAutoCompleteCache->getTicker()->getId()
				);
				$tickerAutoComplete->setTicker($ticker);
			}
		}

		/**
		 * @var \Symfony\Component\Form\FormInterface $form
		 */
		$form = $this->createForm(
			TickerAutocompleteType::class,
			$tickerAutoComplete,
			['extra_options' => ['include_all_tickers' => true]]
		);
		$form->handleRequest($request);
		if ($form->isSubmitted() && $form->isValid()) {
			$ticker = $tickerAutoComplete->getTicker();
			$request->getSession()->set(self::SESSION_KEY, $tickerAutoComplete);
		}

		$queryBuilder = $calendarRepository->getAllQuery(
			$sort,
			$orderBy,
			$ticker
		);
		$adapter = new QueryAdapter($queryBuilder);
		$pager = new Pagerfanta($adapter);
		$pager->setMaxPerPage(10);
		$pager->setCurrentPage($page);

		return $this->render('calendar/index.html.twig', [
			'form' => $form,
			'pager' => $pager,
			'thisPage' => $page,
			'orderBy' => $orderBy,
			'sort' => $sort,
		]);
	}

	#[
		Route(
			path: '/create/{ticker?}',
			name: 'calendar_new',
			methods: ['GET', 'POST']
		)
	]
	public function create(
		Request $request,
		EntityManagerInterface $entityManager,
		?Ticker $ticker,
		Referer $referer
	): Response {
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

			if ($referer->get()) {
				return $this->redirect($referer->get());
			}
			return $this->redirectToRoute('calendar_index');
		}

		return $this->render('calendar/new.html.twig', [
			'calendar' => $calendar,
			'form' => $form->createView(),
		]);
	}

	#[
		Route(
			path: '/calendarperdatetable',
			name: 'calendar_per_date_table',
			methods: ['GET', 'POST']
		)
	]
	public function viewCalendarTable(
		Request $request,
		PositionRepository $positionRepository,
		DividendDataProvider $dividendDataProvider,
		TransactionRepository $transactionRepository,
		CorporateActionDataProvider $corporateActionDataProvider,
		DividendExchangeRateResolverInterface $dividendExchangeRateResolver,
		TransactionAdjuster $transactionAdjuster,
		DividendAdjuster $dividendAdjuster,
		DividendCalendarService $dividendCalendarService
	): Response {
		$year = (int) date('Y');
		$endDate = $year . '-12-31';

		$dateSelect = new DateSelect();
		$dateSelect
			->setStartdate(new DateTime('now'))
			->setEnddate(new DateTime($endDate));
		$form = $this->createForm(CalendarDividendType::class, $dateSelect);
		$form->handleRequest($request);

		if ($form->isSubmitted() && $form->isValid()) {
			$dateSelect = $form->getData();
		}

		// 1. Get the positions
		// 2. Get the tickers with joined tax and currency
		// 3. Get the transactions
		// 4. get the corporate actions
		// 5. get the calendars for tickers and paymentDate in between start and end date
		// 6. get the exchange rate -> This can be USD, EUR etc So it should be determined by ticker currency
		// 7. get the adjusted amount
		// 8. get  the adjusted dividends
		// 9. execute generate() and gat the data array needed to build the view
		// 10. build the view

		// Step 1.
		$positionData = $positionRepository->getForCalendarView();
		$positionIds = array_map(function ($position) {
			return $position->getId();
		}, $positionData);

		dd($positionData);

		// Step 2.
		$tickers = [];
		$tickerIds = [];
		$positions = [];
		foreach ($positionData as $position) {
			$ticker = $position->getTicker();
			$tickers[$ticker->getId()] = $ticker;
			$tickerIds[] =$ticker->getId();
			$positions[$ticker->getId()] = $position;
		}

		// Step 3.
		$transactionData = $transactionRepository->getForCalendarView(
			$positionIds
		);
		unset($positionIds);
		unset($positionData);


		$transactions = [];
		foreach ($transactionData as $transaction) {
			$tickerId = $transaction->getPosition()->getTicker()->getId();
			$transactions[$tickerId][] = $transaction;
		}

		// Step 4
		$corporateActions = $corporateActionDataProvider->load($tickers);


		// Set 5
		$calendars = $dividendDataProvider->load(
			tickers: $tickers,
			afterDate: $dateSelect->getStartdate(),
			beforeDate: $dateSelect->getEnddate(),
			types: [Calendar::REGULAR, Calendar::SPECIAL]
		);

		$exchangeRates = [];
		// Step 6, 7 and 8
		foreach ($tickers as $ticker) {
			$tickerId = $ticker->getId();
			$exchangeRates[$tickerId] = $dividendExchangeRateResolver->getRateForTicker($ticker);

			if (isset($transactions[$tickerId])) {
				foreach ($transactions[$tickerId] as $transaction) {
					$transactionAdjuster->getAdjustedAmount(
						$transaction,
						$corporateActions[$tickerId] ?? []
					);
				}
			}
			if (isset($calendars[$tickerId])) {
				foreach ($calendars[$tickerId] as $calendar) {
					$dividendAdjuster->getAdjustedDividend(
						$calendar,
						$corporateActions[$tickerId] ?? []
					);
				}
			}
		}

		$dividendCalendarService->load(
			tickers: $tickers,
			calendars: $calendars,
			transactions: $transactions,
            positions: $positions,
			exchangeRates: $exchangeRates
		);

		$calendarData = $dividendCalendarService->generate();

		return $this->render('calendar/view_table.html.twig', [
			'calendars' => $calendarData,
			'year' => $year,
			'form' => $form->createView(),
		]);
	}

	#[Route(path: '/{id}', name: 'calendar_show', methods: ['GET'])]
	public function show(Calendar $calendar): Response
	{
		return $this->render('calendar/show.html.twig', [
			'calendar' => $calendar,
		]);
	}

	#[
		Route(
			path: '/{id}/edit',
			name: 'calendar_edit',
			methods: ['GET', 'POST']
		)
	]
	public function edit(
		Request $request,
		EntityManagerInterface $entityManager,
		Calendar $calendar,
		Referer $referer
	): Response {
		$form = $this->createForm(CalendarType::class, $calendar);
		$form->handleRequest($request);

		if ($form->isSubmitted() && $form->isValid()) {
			$entityManager->persist($calendar);
			$entityManager->flush();

			if ($referer->get()) {
				return $this->redirect($referer->get());
			}
			return $this->redirectToRoute('calendar_index');
		}

		return $this->render('calendar/edit.html.twig', [
			'calendar' => $calendar,
			'form' => $form->createView(),
			'referer' => $referer->get() ?: null,
		]);
	}

	#[Route(path: '/delete/{id}', name: 'calendar_delete', methods: ['POST'])]
	public function delete(
		Request $request,
		EntityManagerInterface $entityManager,
		Calendar $calendar,
		Referer $referer
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

		if ($referer->get()) {
			return $this->redirect($referer->get());
		}
		return $this->redirectToRoute('calendar_index');
	}
}
