<?php

namespace App\Controller;

use App\Entity\Calendar;
use App\Entity\Ticker;
use App\Form\CalendarType;
use App\Repository\CalendarRepository;
use App\Repository\PaymentRepository;
use App\Service\Referer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 */
#[Route(path: '/{_locale<%app.supported_locales%>}/dashboard/calendar')]
class CalendarController extends AbstractController
{
	#[Route(path: '/', name: 'calendar_index', methods: ['GET'])]
	public function index(
		CalendarRepository $calendarRepository,
		PaymentRepository $paymentRepository
	): Response {
		// @phpstan-ignore-next-line method is missing from repository, pre-existing
		$calendars = $calendarRepository->getCalenderWithPayments();

		if (!$calendars) {
			//no required calendar items found on first use
			return $this->render('calendar/index.html.twig', [
				'calendars' => [],
			]);
		}

		return $this->render('calendar/index.html.twig', [
			'calendars' => $calendars,
			'paymentRepository' => $paymentRepository,
		]);
	}

	#[Route('/create/{ticker?}', name: 'calendar_create', methods: ['GET', 'POST'])]
	public function create(
		Request $request,
		EntityManagerInterface $entityManager,
		#[MapEntity] ?Ticker $ticker,
		Referer $referer
	): Response {
		$referer->set('calendar_index');
		$calendar = new Calendar();
		if ($ticker != null) {
			$calendar->setTicker($ticker);
		}
		$form = $this->createForm(CalendarType::class, $calendar);
		$form->handleRequest($request);

		if ($form->isSubmitted() && $form->isValid()) {
			$entityManager->persist($calendar);
			$entityManager->flush();

			return $this->redirectToRoute('calendar_index');
		}

		return $this->render('calendar/new.html.twig', [
			'calendar' => $calendar,
			'form' => $form->createView(),
		]);
	}

	#[Route('/{id}', name: 'calendar_show', methods: ['GET'])]
	public function show(#[MapEntity] Calendar $calendar): Response
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
		#[MapEntity] Calendar $calendar,
		Referer $referer
	): Response {
		$form = $this->createForm(CalendarType::class, $calendar);
		$form->handleRequest($request);

		if ($form->isSubmitted() && $form->isValid()) {
			$entityManager->flush();

			return $this->redirectToRoute('calendar_index');
		}

		return $this->render('calendar/edit.html.twig', [
			'calendar' => $calendar,
			'form' => $form->createView(),
			'referer' => $referer->get() ?: null,
		]);
	}

	#[Route('/delete/{id}', name: 'calendar_delete', methods: ['POST'])]
	public function delete(
		Request $request,
		EntityManagerInterface $entityManager,
		#[MapEntity] Calendar $calendar,
		Referer $referer
	): Response {
		if (
			$this->isCsrfTokenValid(
				'delete' . $calendar->getId(),
				$request->request->get('_token')
			)
		) {
			$entityManager->remove($calendar);
			$entityManager->flush();
		}

		return $this->redirectToRoute('calendar_index');
	}
}
