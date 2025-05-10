<?php

namespace App\Controller;

use App\Entity\TickerAlternativeSymbol;
use App\Form\TickerAlternativeSymbolType;
use App\Repository\TickerAlternativeSymbolRepository;
use Doctrine\ORM\EntityManagerInterface;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;

#[
	Route(
		path: '/{_locale<%app.supported_locales%>}/dashboard/admin/ticker/symbol'
	)
]
final class TickerAlternativeSymbolController extends AbstractController
{
	#[Route(name: 'app_ticker_alternative_symbol_index', methods: ['GET'])]
	public function index(
		TickerAlternativeSymbolRepository $tickerAlternativeSymbolRepository,
		#[MapQueryParameter] int $page = 1
	): Response {
		$queryBuilder = $tickerAlternativeSymbolRepository->getQueryBuilderFindByAll();
		$adapter = new QueryAdapter($queryBuilder);
		$pager = new Pagerfanta($adapter);
		$pager->setMaxPerPage(10);
		$pager->setCurrentPage($page);

		return $this->render('ticker_alternative_symbol/index.html.twig', [
			'pager' => $pager,
			'title' => 'Alternative ticker symbol',
			'create_path' => 'app_ticker_alternative_symbol_new',
		]);
	}

	#[
		Route(
			'/new',
			name: 'app_ticker_alternative_symbol_new',
			methods: ['GET', 'POST']
		)
	]
	public function new(
		Request $request,
		EntityManagerInterface $entityManager
	): Response {
		$tickerAlternativeSymbol = new TickerAlternativeSymbol();
		$form = $this->createForm(
			TickerAlternativeSymbolType::class,
			$tickerAlternativeSymbol
		);
		$form->handleRequest($request);

		if ($form->isSubmitted() && $form->isValid()) {
			$entityManager->persist($tickerAlternativeSymbol);
			$entityManager->flush();

			return $this->redirectToRoute(
				'app_ticker_alternative_symbol_index',
				[],
				Response::HTTP_SEE_OTHER
			);
		}
		return $this->render('ticker_alternative_symbol/new.html.twig', [
			'entity' => $tickerAlternativeSymbol,
			'form' => $form,
			'title' => 'Create new alternative sy,bol',
			'back_path' => 'app_ticker_alternative_symbol_index',
		]);
	}

	#[
		Route(
			'/{id}',
			name: 'app_ticker_alternative_symbol_show',
			methods: ['GET']
		)
	]
	public function show(
		TickerAlternativeSymbol $tickerAlternativeSymbol
	): Response {
		return $this->render('ticker_alternative_symbol/show.html.twig', [
			'ticker_alternative_symbol' => $tickerAlternativeSymbol,
			'entity' => $tickerAlternativeSymbol,
			'title' => 'Show ' . $tickerAlternativeSymbol->getSymbol(),
			'delete_template' =>
				'ticker_alternative_symbol/_delete_form.html.twig',
			'delete_path' => 'app_ticker_alternative_symbol_delete',
			'back_path' => 'app_ticker_alternative_symbol_index',

			'edit_path' => 'app_ticker_alternative_symbol_edit',
			'can_remove' => 1,
		]);
	}

	#[
		Route(
			'/{id}/edit',
			name: 'app_ticker_alternative_symbol_edit',
			methods: ['GET', 'POST']
		)
	]
	public function edit(
		Request $request,
		TickerAlternativeSymbol $tickerAlternativeSymbol,
		EntityManagerInterface $entityManager
	): Response {
		$form = $this->createForm(
			TickerAlternativeSymbolType::class,
			$tickerAlternativeSymbol
		);
		$form->handleRequest($request);

		if ($form->isSubmitted() && $form->isValid()) {
			$entityManager->flush();

			return $this->redirectToRoute(
				'app_ticker_alternative_symbol_index',
				[],
				Response::HTTP_SEE_OTHER
			);
		}

		return $this->render('ticker_alternative_symbol/edit.html.twig', [
			'entity' => $tickerAlternativeSymbol,
			'form' => $form,
			'title' => 'Edit ' . $tickerAlternativeSymbol->getSymbol(),
			'delete_template' =>
				'ticker_alternative_symbol/_delete_form.html.twig',
			'back_path' => 'app_ticker_alternative_symbol_index',
			'delete_path' => 'app_ticker_alternative_symbol_delete',
			'can_remove' => 1,
		]);
	}

	#[
		Route(
			'/{id}',
			name: 'app_ticker_alternative_symbol_delete',
			methods: ['POST']
		)
	]
	public function delete(
		Request $request,
		TickerAlternativeSymbol $tickerAlternativeSymbol,
		EntityManagerInterface $entityManager
	): Response {
		if (
			$this->isCsrfTokenValid(
				'delete' . $tickerAlternativeSymbol->getId(),
				$request->getPayload()->getString('_token')
			)
		) {
			$entityManager->remove($tickerAlternativeSymbol);
			$entityManager->flush();
		}

		return $this->redirectToRoute(
			'app_ticker_alternative_symbol_index',
			[],
			Response::HTTP_SEE_OTHER
		);
	}
}
