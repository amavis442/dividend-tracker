<?php

namespace App\Controller\Portfolio;

use App\Entity\Pie;
use App\Entity\Position;
use App\Service\DividendService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\TransactionRepository;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\Transaction;
use App\Form\PositionPieType;
use App\Form\TransactionPieType;
use App\Repository\PieRepository;
use App\Repository\PositionRepository;
use Doctrine\ORM\EntityManagerInterface;

/** @psalm-suppress PropertyNotSetInConstructor */
#[Route(path: '/{_locale<%app.supported_locales%>}/dashboard/portfolio')]
class TransactionController extends AbstractController
{
	public const SESSION_KEY = 'portfoliocontroller_session';

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

		$highAndLowPriceTransaction = $transactionRepository->getHighLow(
			$position
		);

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
				'highAndLowPriceTransaction' => $highAndLowPriceTransaction,
			]
		);
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

		$form = $this->createForm(TransactionPieType::class, $transaction);

		$form->handleRequest($request);
		if ($form->isSubmitted() && $form->isValid()) {
			$entityManager->persist($transaction);
			$entityManager->flush();

			return $this->render(
				'portfolio/show/transaction/_transaction_pie.html.twig',
				[
					'transaction' => $transaction,
				]
			);
		}

		return $this->render('portfolio/show/transaction/_form_update_pie.html.twig', [
			'transaction' => $transaction,
			'form' => $form,
			'formTarget' => 'update-pie-' . $transaction->getId(),
		]);
	}

		#[
		Route(
			path: '/updatepiebulk/{id}',
			name: 'portfolio_update_pie_bulk',
			methods: ['POST', 'GET']
		)
	]
	public function updatePieBulk(
		Request $request,
		Position $position,
		EntityManagerInterface $entityManager,
		TransactionRepository $transactionRepository,
		PositionRepository $positionRepository,
	): Response {
		//$transaction = $transactionRepository->find($transaction->getId());
		$form = $this->createForm(PositionPieType::class, $position);

		$form->handleRequest($request);
		if ($form->isSubmitted() && $form->isValid()) {
			$pie = $position->getPies()[0];

			$transactionRepository->updatePie($position, $pie);


			$entityManager->persist($position);
			$entityManager->flush();

			return $this->render(
				'portfolio/show/transaction/_transaction_pie_bulk.html.twig',
				[
					'position' => $position,
				]
			);
		}

		return $this->render('portfolio/show/transaction/_form_update_pie_bulk.html.twig', [
			'position' => $position,
			'form' => $form,
			'formTarget' => 'update-pie-bulk',
		]);
	}
}
