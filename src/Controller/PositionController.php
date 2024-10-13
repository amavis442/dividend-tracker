<?php

namespace App\Controller;

use App\Entity\Portfolio;
use App\Entity\Position;
use App\Entity\TickerAutocomplete;
use App\Form\PositionType;
use App\Form\TickerAutocompleteType;
use App\Repository\PortfolioRepository;
use App\Repository\PositionRepository;
use App\Repository\TickerRepository;
use App\Repository\TransactionRepository;
use App\Service\Referer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;

#[Route(path: '/{_locale<%app.supported_locales%>}/dashboard/position')]
class PositionController extends AbstractController
{
	public const SESSION_KEY = 'positioncontroller_session';

	#[
		Route(
			path: '/list/{page}/{tab}/{orderBy}/{sort}/{status}',
			name: 'position_index',
			methods: ['GET', 'POST']
		)
	]
	public function index(
		Request $request,
		PortfolioRepository $portfolioRepository,
		PositionRepository $positionRepository,
		TickerRepository $tickerRepository,
		Referer $referer,
		#[MapQueryParameter] int $page = 1,
		#[MapQueryParameter] string $tab = 'All',
		#[MapQueryParameter] string $orderBy = 'symbol',
		#[MapQueryParameter] string $sort = 'asc',
		#[MapQueryParameter] int $status = PositionRepository::CLOSED
	): Response {
		if (!in_array($sort, ['asc', 'desc', 'ASC', 'DESC'])) {
			$sort = 'asc';
		}

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

		$referer->set('position_index', ['status' => $status]);

		/**
		 * @var \App\Entity\User $user
		 */
		$user = $this->getUser();
		$portfolio = $portfolioRepository->findOneBy([
			'user' => $user->getId(),
		]);
		if (!$portfolio) {
			$portfolio = new Portfolio(); // do not want to throw an exception, but just use an empty entity
		}

		$queryBuilder = $positionRepository->getAllQuery(
			$orderBy,
			$sort,
			$ticker
		);
		$adapter = new QueryAdapter($queryBuilder);
		$pager = new Pagerfanta($adapter);
		$pager->setMaxPerPage(10);
		$pager->setCurrentPage($page);

		return $this->render('position/index.html.twig', [
			'pager' => $pager,
			'portfolio' => $portfolio,
			'form' => $form,
			'thisPage' => $page,
			'order' => $orderBy,
			'sort' => $sort,
			'status' => $status,
			'tab' => $tab,
		]);
	}

	#[Route(path: '/show/{id}/{page}', name: 'position_show', methods: ['GET'])]
	public function show(
		Position $position,
		TransactionRepository $transactionRepository,
		int $page = 1
	): Response {
		$queryBuilder = $transactionRepository->getAllByPositionQuery(
			$position
		);
		$adapter = new QueryAdapter($queryBuilder);
		$pager = new Pagerfanta($adapter);
		$pager->setMaxPerPage(10);
		$pager->setCurrentPage($page);

		return $this->render('position/show.html.twig', [
			'position' => $position,
			'pager' => $pager,
			'ticker' => $position->getTicker(),
			'netYearlyDividend' => 0.0,
		]);
	}

	#[
		Route(
			path: '/edit/{id}',
			name: 'position_edit',
			methods: ['GET', 'POST']
		)
	]
	public function edit(
		Request $request,
		Position $position,
		Referer $referer,
		EntityManagerInterface $entityManager
	): Response {
		$form = $this->createForm(PositionType::class, $position);
		$form->handleRequest($request);

		if ($form->isSubmitted() && $form->isValid()) {
			$request
				->getSession()
				->set(self::SESSION_KEY, $position->getTicker()->getSymbol());

			$refLink = $referer->get();
			if ($refLink != null) {
				return $this->redirect($refLink);
			}

			$entityManager->persist($position);
			$entityManager->flush();
			return $this->redirectToRoute('position_index');
		}

		return $this->render('position/edit.html.twig', [
			'position' => $position,
			'form' => $form->createView(),
		]);
	}

	#[
		Route(
			path: '/delete/{id}',
			name: 'position_delete',
			methods: ['POST', 'DELETE']
		)
	]
	public function delete(
		Request $request,
		EntityManagerInterface $entityManager,
		Position $position
	): Response {
		if ($position->getId() == null) {
			throw new \RuntimeException('No position to remove');
		}
		$position_id = (int) $position->getId();
		if (
			$this->isCsrfTokenValid(
				'delete' . $position_id,
				(string) $request->request->get('_token', '')
			)
		) {
			$entityManager->remove($position);
			$entityManager->flush();
		}

		return $this->redirectToRoute('position_index');
	}
}
