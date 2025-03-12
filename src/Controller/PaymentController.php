<?php

namespace App\Controller;

use App\Entity\Constants;
use App\Entity\DateIntervalSelect;
use App\Entity\Payment;
use App\Entity\Position;
use App\Form\DateIntervalFormType;
use App\Form\PaymentType;
use App\Helper\DateHelper;
use App\Repository\CalendarRepository;
use App\Repository\PaymentRepository;
use App\Repository\TickerRepository;
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

#[Route(path: '/{_locale<%app.supported_locales%>}/dashboard/payment')]
class PaymentController extends AbstractController
{
	public const SESSION_KEY = 'paymentcontroller_session';

	#[
		Route(
			path: '/',
			name: 'payment_index',
			methods: ['GET', 'POST']
		)
	]
	public function index(
		Request $request,
		PaymentRepository $paymentRepository,
		TickerRepository $tickerRepository,
		Referer $referer,
		#[MapQueryParameter]int $page = 1
	): Response {
		$referer->set('payment_index', [
			'page' => $page,
		]);

		$sort = 'payDate';
		$orderBy = 'DESC';
		$ticker = null;
		$year = (int) date('Y');
		$month = null;
		$qautor = null;

		$dateIntervalSelect = new DateIntervalSelect();
		$dateIntervalSelect->setYear((int) date('Y'));

		$sessionFormData = $request->getSession()->get(self::SESSION_KEY, null);
		if ($sessionFormData instanceof DateIntervalSelect) {
			$year = $sessionFormData->getYear();
			$month = $sessionFormData->getMonth();
			$qautor = $sessionFormData->getQuator();

			$dateIntervalSelect
				->setYear($year)
				->setMonth($month)
				->setQuator($qautor);

			if ($sessionFormData->getTicker() != null) {
				$ticker_id = $sessionFormData->getTicker()->getId();
				$ticker = $tickerRepository->find($ticker_id);
				$dateIntervalSelect->setTicker($ticker);
			}
		}

		$form = $this->createForm(
			DateIntervalFormType::class,
			$dateIntervalSelect,
			[
				'startYear' => 2019,
				'extra_options' => [
					'include_all_tickers' => false,
				],
			]
		);

		$form->handleRequest($request);
		if ($form->isSubmitted() && $form->isValid()) {
			$page = 1;
			$year = $dateIntervalSelect->getYear();
			$month = $dateIntervalSelect->getMonth();
			$qautor = $dateIntervalSelect->getQuator();
			$ticker = $dateIntervalSelect->getTicker();

			$request->getSession()->set(self::SESSION_KEY, $dateIntervalSelect);
		}

		[$startDate, $endDate] = [$year . '-01-01', $year . '-12-31'];
		if ($month && $month !== 0) {
			[$startDate, $endDate] = (new DateHelper())->monthToDates(
				$month,
				$year
			);
		}

		if ($qautor) {
			[$startDate, $endDate] = (new DateHelper())->quaterToDates(
				$qautor,
				$year
			);
		}
		$totalDividend = $paymentRepository->getTotalDividend(
			$startDate . ' 00:00:00',
			$endDate . ' 23:59:59',
			$ticker
		);

		// TODO: Make this dynamic because not all stocks have 15% dividend tax
		$taxes = ($totalDividend / (100 - Constants::TAX)) * Constants::TAX;

		$queryBuilder = $paymentRepository->getAllQuery(
			$sort,
			$orderBy,
			$ticker,
			$startDate,
			$endDate
		);

		$adapter = new QueryAdapter($queryBuilder);
		$pager = new Pagerfanta($adapter);
		$pager->setMaxPerPage(10);
		$pager->setCurrentPage($page);

		return $this->render('payment/index.html.twig', [
			'form' => $form,
			'pager' => $pager,
			'dividends' => $totalDividend,
			'taxes' => $taxes,
			'thisPage' => $page,
			'order' => $orderBy,
			'sort' => $sort,
			'routeName' => 'payment_index',
			'searchPath' => 'payment_search',
			'startDate' => $startDate,
			'endDate' => $endDate,
		]);
	}

	#[
		Route(
			path: '/create/{position}/{timestamp?}',
			name: 'payment_new',
			methods: ['GET', 'POST']
		)
	]
	public function create(
		Request $request,
		EntityManagerInterface $entityManager,
		position $position,
		string $timestamp,
		CalendarRepository $calendarRepository,
		Referer $referer
	): Response {
		$ticker = $position->getTicker();
		if ($timestamp) {
			$year = (int) substr($timestamp, 0, 4);
			$month = (int) substr($timestamp, 5, 2);
		} else {
			$year = (int) date('Y');
			$month = (int) date('m');
		}

		$positionDividendEstimate = $calendarRepository->getDividendEstimate(
			$position,
			$year
		);
		if (isset($positionDividendEstimate[$timestamp])) {
			$data =
				$positionDividendEstimate[$timestamp]['tickers'][
					$ticker->getSymbol()
				];
			$amount = $data['amount'];
			$calendar = $data['calendar'];
		} else {
			$amount = $position->getAmount();
			$calendar = $calendarRepository->getLastDividend($ticker);
		}
		$payment = new Payment();
		$payment->setAmount($amount);
		$payment->setPosition($position);

		if ($calendar) {
			$payment->setCalendar($calendar);
			$payment->setDividend($calendar->getCashAmount());
		}
		$payment->setPayDate(new DateTime());

		$form = $this->createForm(PaymentType::class, $payment);
		$form->handleRequest($request);

		if ($form->isSubmitted() && $form->isValid()) {
			$payment->setTicker($ticker);
			$entityManager->persist($payment);
			$entityManager->flush();

			if ($referer->get()) {
				return $this->redirect($referer->get());
			}
			return $this->redirectToRoute('payment_index');
		}

		return $this->render('payment/new.html.twig', [
			'payment' => $payment,
			'form' => $form->createView(),
		]);
	}

	#[Route(path: '/{id}', name: 'payment_show', methods: ['GET'])]
	public function show(Payment $payment): Response
	{
		return $this->render('payment/show.html.twig', [
			'payment' => $payment,
		]);
	}

	#[Route(path: '/{id}/edit', name: 'payment_edit', methods: ['GET', 'POST'])]
	public function edit(
		Request $request,
		EntityManagerInterface $entityManager,
		Payment $payment,
		Referer $referer
	): Response {
		$form = $this->createForm(PaymentType::class, $payment);
		$form->handleRequest($request);

		if ($form->isSubmitted() && $form->isValid()) {
			$entityManager->flush();

			if ($referer->get()) {
				return $this->redirect($referer->get());
			}
			return $this->redirectToRoute('payment_index');
		}

		return $this->render('payment/edit.html.twig', [
			'payment' => $payment,
			'form' => $form->createView(),
		]);
	}

	#[
		Route(
			path: '/delete/{id}',
			name: 'payment_delete',
			methods: ['POST', 'DELETE']
		)
	]
	public function delete(
		Request $request,
		EntityManagerInterface $entityManager,
		Payment $payment,
		Referer $referer
	): Response {
		if (
			$this->isCsrfTokenValid(
				'delete' . $payment->getId(),
				$request->request->get('_token')
			)
		) {
			$entityManager->remove($payment);
			$entityManager->flush();
		}

		if ($referer->get()) {
			return $this->redirect($referer->get());
		}
		return $this->redirectToRoute('payment_index');
	}
}
