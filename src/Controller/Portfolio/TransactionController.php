<?php

namespace App\Controller\Portfolio;

use App\Entity\Position;
use App\Service\DividendService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Stopwatch\Stopwatch;
use App\Repository\TransactionRepository;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Pagerfanta;

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

		$highAndLowPriceTransaction = $transactionRepository->getHighLow($position);

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
}
