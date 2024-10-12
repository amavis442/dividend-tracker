<?php

namespace App\Controller;

use App\Entity\Branch;
use App\Form\BranchType;
use App\Repository\BranchRepository;
use App\Service\Referer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Pagerfanta;

#[Route('/{_locale<%app.supported_locales%>}/dashboard/branch')]
class BranchController extends AbstractController
{
	#[Route('/list/{page<\d+>?1}', name: 'branch_index', methods: ['GET'])]
	public function index(
		BranchRepository $branchRepository,
		int $page = 1,
		Referer $referer
	): Response {
		$referer->clear();
		$referer->set('branch_index', [
            'page' => $page,
        ]);

		$queryBuilder = $branchRepository->getAllQuery();
		$adapter = new QueryAdapter($queryBuilder);
		$pager = new Pagerfanta($adapter);
		$pager->setMaxPerPage(10);
		$pager->setCurrentPage($page);

		$sumAssetAllocation = $branchRepository->getSumAssetAllocation();

		return $this->render('branch/index.html.twig', [
			'pager' => $pager,
			'sumAssetAllocation' => $sumAssetAllocation,
			'routeName' => 'branch_index',
		]);
	}

	#[Route('/create', name: 'branch_new', methods: ['GET', 'POST'])]
	public function create(
		Request $request,
		BranchRepository $branchRepository,
		EntityManagerInterface $entityManager
	): Response {
		$branch = new Branch();
		$assignedAllocation =
			$branchRepository->getSumAssetAllocation() -
			$branch->getAssetAllocation();
		$maxAssetAllocation = 100 - (int) ($assignedAllocation / 100);
		$form = $this->createForm(BranchType::class, $branch, [
			'maxAssetAllocation' => $maxAssetAllocation,
		]);
		$form->handleRequest($request);

		if ($form->isSubmitted() && $form->isValid()) {
			$entityManager->persist($branch);
			$entityManager->flush();

			return $this->redirectToRoute('branch_index');
		}

		return $this->render('branch/new.html.twig', [
			'branch' => $branch,
			'form' => $form->createView(),
		]);
	}

	#[Route('/{id}', name: 'branch_show', methods: ['GET'])]
	public function show(Branch $branch): Response
	{
		return $this->render('branch/show.html.twig', [
			'branch' => $branch,
		]);
	}

	#[Route('/{id}/edit', name: 'branch_edit', methods: ['GET', 'POST'])]
	public function edit(
		Request $request,
		EntityManagerInterface $entityManager,
		Branch $branch,
		BranchRepository $branchRepository
	): Response {
		$assignedAllocation =
			$branchRepository->getSumAssetAllocation() -
			$branch->getAssetAllocation();
		$maxAssetAllocation = 100 - (int) ($assignedAllocation / 100);
		$form = $this->createForm(BranchType::class, $branch, [
			'maxAssetAllocation' => $maxAssetAllocation,
		]);
		$form->handleRequest($request);

		if ($form->isSubmitted() && $form->isValid()) {
			$entityManager->flush();

			return $this->redirectToRoute('branch_index');
		}

		return $this->render('branch/edit.html.twig', [
			'branch' => $branch,
			'form' => $form->createView(),
		]);
	}

	#[Route('/delete/{id}', name: 'branch_delete', methods: ['POST', 'DELETE'])]
	public function delete(
		Request $request,
		EntityManagerInterface $entityManager,
		Branch $branch
	): Response {
		if (
			$this->isCsrfTokenValid(
				'delete' . $branch->getId(),
				$request->request->get('_token')
			)
		) {
			$entityManager->remove($branch);
			$entityManager->flush();
		}

		return $this->redirectToRoute('branch_index');
	}
}
