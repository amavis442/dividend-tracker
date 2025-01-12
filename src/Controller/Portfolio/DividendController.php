<?php

namespace App\Controller\Portfolio;

use App\Entity\Calendar;
use App\Entity\Position;
use App\Entity\Ticker;
use App\Form\CalendarType;
use App\Repository\PaymentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Stopwatch\Stopwatch;
use Pagerfanta\Adapter\ArrayAdapter;
use Pagerfanta\Pagerfanta;

/** @psalm-suppress PropertyNotSetInConstructor */
#[Route(path: '/{_locale<%app.supported_locales%>}/dashboard/portfolio')]
class DividendController extends AbstractController
{
	public const SESSION_KEY = 'portfoliocontroller_session';

	public function __construct(private Stopwatch $stopwatch)
	{
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
	public function deleteDividend(
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
}
