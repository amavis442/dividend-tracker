<?php

namespace App\Controller;

use App\Entity\Calendar;
use App\Entity\DateSelect;
use App\Entity\Ticker;
use App\Entity\TickerAutocomplete;
use App\Form\CalendarDividendType;
use App\Form\CalendarType;
use App\Form\TickerAutocompleteType;
use App\Repository\CalendarRepository;
use App\Repository\TickerRepository;
use App\Repository\PositionRepository;
use App\Service\DividendService;
use App\Service\Referer;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;

#[Route(path: '/{_locale<%app.supported_locales%>}/dashboard/calendar')]
class CalendarController extends AbstractController
{
	public const SESSION_KEY = 'calendar_searchCriteria';

	#[Route(path: '/', name: 'calendar_index', methods: ['GET', 'POST'])]
	public function index(
		Request $request,
		CalendarRepository $calendarRepository,
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
		CalendarRepository $calendarRepository,
		PositionRepository $positionRepository,
		DividendService $dividendService
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

		$calendars = $calendarRepository->groupByMonth(
			$dividendService,
			$year,
			$dateSelect->getStartdate()->format('Y-m-d'),
			$dateSelect->getEnddate()->format('Y-m-d')
		);

		return $this->render('calendar/view_table.html.twig', [
			'calendars' => $calendars,
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
