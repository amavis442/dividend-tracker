<?php

namespace App\Controller\Portfolio;

use App\Entity\CorporateAction;
use App\Entity\Position;
use App\Form\CorporateActionType;
use App\Repository\CorporateActionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Annotation\Route;

/** @psalm-suppress PropertyNotSetInConstructor */
#[Route(path: '/{_locale<%app.supported_locales%>}/dashboard/portfolio')]
class CorporateActionController extends AbstractController
{
	#[
		Route(
			path: '/show/corporate/action/list/{position}/{page}',
			name: 'portfolio_list_corporate_action',
			methods: ['GET']
		)
	]
	public function index(
		Position $position,
		CorporateActionRepository $corporateActionRepository,
		#[MapQueryParameter] int $page = 1,
		#[MapQueryParameter] string $orderBy = 'eventDate',
		#[MapQueryParameter] string $sort = 'desc'
	): Response {
		$adapter = new QueryAdapter(
			$corporateActionRepository->getBuilderFindAllByPosition($position)
		);
		$pager = new Pagerfanta($adapter);
		$pager->setMaxPerPage(10);
		$pager->setCurrentPage($page);

		return $this->render(
			'portfolio/show/corporate_action/_index.html.twig',
			[
				'pager' => $pager,
				'thisPage' => $page,
				'order' => $orderBy,
				'sort' => $sort,
				'position' => $position,
			]
		);
	}

	#[
		Route(
			path: '/show/corporateaction/show/{id}/{page}',
			name: 'portfolio_show_corporate_action',
			methods: ['GET']
		)
	]
	public function show(
		CorporateAction $corporateAction,
		CorporateActionRepository $corporateActionRepository,
		int $page = 1
	): Response {
		return $this->render(
			'portfolio/show/corporate_action/_placeholder.html.twig',
			[]
		);
	}

	#[
		Route(
			path: '/show/corporate/action/create/{position}',
			name: 'portfolio_create_corporate_action',
			methods: ['GET', 'POST']
		)
	]
	public function create(
		Request $request,
		EntityManagerInterface $entityManager,
		?Position $position
	): Response {
		$corporateAction = new CorporateAction();
		if ($position) {
			$corporateAction->setTicker($position->getTicker());
		}

		$form = $this->createForm(CorporateActionType::class, $corporateAction);
		$form->handleRequest($request);
		if ($form->isSubmitted() && $form->isValid()) {
			$entityManager->persist($corporateAction);
			$entityManager->flush();

			return $this->redirectToRoute('portfolio_list_corporate_action', [
				'position' => $position->getId(),
				'page' => 1,
			]);
		}

		return $this->render('portfolio/show/corporate_action/_form_create.html.twig', [
			'corporateAction' => $corporateAction,
			'position' => $position,
			'form' => $form->createView(),
		]);
	}

	#[
		Route(
			path: '/show/corporate/action/delete/{corporateAction}/{position}',
			name: 'portfolio_delete_corporate_action',
			methods: ['POST']
		)
	]
	public function delete(
		Request $request,
		EntityManagerInterface $entityManager,
		CorporateAction $corporateAction,
		Position $position
	): Response {
		if (
			$this->isCsrfTokenValid(
				'delete' . $corporateAction->getId(),
				$request->request->get('_token')
			)
		) {
			$entityManager->remove($corporateAction);
			$entityManager->flush();
			$this->addFlash('notice', 'Removed corporate action.');
		}

		return $this->redirectToRoute('portfolio_list_corporate_action', [
			'id' => $position->getId(),
			'page' => 1,
		]);
	}
}
