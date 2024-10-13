<?php

namespace App\Controller;

use App\Entity\Currency;
use App\Form\CurrencyType;
use App\Repository\CalendarRepository;
use App\Repository\CurrencyRepository;
use App\Repository\PaymentRepository;
use App\Repository\PositionRepository;
use App\Repository\TickerRepository;
use App\Service\Referer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/{_locale<%app.supported_locales%>}/dashboard/currency')]
class CurrencyController extends AbstractController
{
	#[Route(path: '/', name: 'currency_index', methods: ['GET'])]
	public function index(CurrencyRepository $currencyRepository): Response
	{
		return $this->render('currency/index.html.twig', [
			'currencies' => $currencyRepository->findAll(),
		]);
	}

	#[Route(path: '/create', name: 'currency_new', methods: ['GET', 'POST'])]
	public function create(
		Request $request,
		EntityManagerInterface $entityManager,
		Referer $referer
	): Response {
		$currency = new Currency();
		$referer->clear();
		$referer->set('journal_index');
		$form = $this->createForm(CurrencyType::class, $currency);
		$form->handleRequest($request);

		if ($form->isSubmitted() && $form->isValid()) {
			$entityManager->persist($currency);
			$entityManager->flush();

			return $this->redirectToRoute('currency_index');
		}

		return $this->render('currency/new.html.twig', [
			'currency' => $currency,
			'form' => $form->createView(),
		]);
	}

	#[Route(path: '/{id}', name: 'currency_show', methods: ['GET'])]
	public function show(Currency $currency): Response
	{
		return $this->render('currency/show.html.twig', [
			'currency' => $currency,
		]);
	}

	#[
		Route(
			path: '/{id}/edit',
			name: 'currency_edit',
			methods: ['GET', 'POST']
		)
	]
	public function edit(
		Request $request,
		EntityManagerInterface $entityManager,
		Currency $currency
	): Response {
		$form = $this->createForm(CurrencyType::class, $currency);
		$form->handleRequest($request);

		if ($form->isSubmitted() && $form->isValid()) {
			$entityManager->flush();

			return $this->redirectToRoute('currency_index');
		}

		return $this->render('currency/edit.html.twig', [
			'currency' => $currency,
			'form' => $form->createView(),
		]);
	}

	#[
		Route(
			path: '/delete/{id}',
			name: 'currency_delete',
			methods: ['POST', 'DELETE']
		)
	]
	public function delete(
		Request $request,
		EntityManagerInterface $entityManager,
		Currency $currency,
		TickerRepository $tickerRepository,
		PositionRepository $positionRepository,
		PaymentRepository $paymentRepository,
		CalendarRepository $calendarRepository
	): Response {
		if (
			$this->isCsrfTokenValid(
				'delete' . $currency->getId(),
				$request->request->get('_token')
			)
		) {
			// Need to check tickers, positions, payments, calendars if there is a mandatory link so you can not remove
			// this item before decoupling them first. Boy oh boy lots of work.
			if (
				($tickerRepository->findOneBy([
					'currency_id' => $currency->getId(),
				]) &&
					$positionRepository->findOneBy([
						'currency_id' => $currency->getId(),
					]) &&
					$paymentRepository->findOneBy([
						'currency_id' => $currency->getId(),
					]) &&
					$calendarRepository->findOneBy([
						'currency_id' => $currency->getId(),
					])) == null
			) {
				$entityManager->remove($currency);
				$entityManager->flush();

				return $this->redirectToRoute('currency_index');
			}
			$this->addFlash(
				'notice',
				'Can not remove currency. It has connections to either ticker, position, calendar and /or payment'
			);
		}

		return $this->redirectToRoute('currency_index');
	}
}
