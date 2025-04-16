<?php

namespace App\Controller\Portfolio;

use App\Entity\Payment;
use App\Entity\Position;
use App\Form\PaymentType;
use App\Repository\PaymentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Pagerfanta;

/** @psalm-suppress PropertyNotSetInConstructor */
#[Route(path: '/{_locale<%app.supported_locales%>}/dashboard/portfolio')]
class PaymentController extends AbstractController
{
	public const SESSION_KEY = 'portfoliocontroller_session';

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


}
