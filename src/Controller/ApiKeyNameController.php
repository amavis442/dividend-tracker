<?php

namespace App\Controller;

use App\Entity\ApiKeyName;
use App\Form\ApiKeyNameType;
use App\Repository\ApiKeyNameRepository;
use Doctrine\ORM\EntityManagerInterface;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/{_locale<%app.supported_locales%>}/dashboard/admin/apikeys')]
final class ApiKeyNameController extends AbstractController
{
	#[Route(name: 'app_api_key_name_index', methods: ['GET'])]
	public function index(
		ApiKeyNameRepository $apiKeyNameRepository,
		#[MapQueryParameter] int $page = 1
	): Response {
		$queryBuilder = $apiKeyNameRepository->getQueryBuilderFindByAll();
		$adapter = new QueryAdapter($queryBuilder);
		$pager = new Pagerfanta($adapter);
		$pager->setMaxPerPage(10);
		$pager->setCurrentPage($page);

		return $this->render('api_key_name/index.html.twig', [
            'create_path' => 'app_api_key_name_new',
            'pager' => $pager,
            'title' => 'ApiKeyName'
		]);
	}

	#[Route('/new', name: 'app_api_key_name_new', methods: ['GET', 'POST'])]
	public function new(
		Request $request,
		EntityManagerInterface $entityManager
	): Response {
		$apiKeyName = new ApiKeyName();
		$form = $this->createForm(ApiKeyNameType::class, $apiKeyName);
		$form->handleRequest($request);

		if ($form->isSubmitted() && $form->isValid()) {
			$entityManager->persist($apiKeyName);
			$entityManager->flush();

			return $this->redirectToRoute(
				'app_api_key_name_index',
				[],
				Response::HTTP_SEE_OTHER
			);
		}

		return $this->render('api_key_name/new.html.twig', [
			'api_key_name' => $apiKeyName,
			'form' => $form,
		]);
	}

	#[Route('/{id}', name: 'app_api_key_name_show', methods: ['GET'])]
	public function show(ApiKeyName $apiKeyName): Response
	{
		return $this->render('api_key_name/show.html.twig', [
			'api_key_name' => $apiKeyName,
		]);
	}

	#[
		Route(
			'/{id}/edit',
			name: 'app_api_key_name_edit',
			methods: ['GET', 'POST']
		)
	]
	public function edit(
		Request $request,
		ApiKeyName $apiKeyName,
		EntityManagerInterface $entityManager
	): Response {
		$form = $this->createForm(ApiKeyNameType::class, $apiKeyName);
		$form->handleRequest($request);

		if ($form->isSubmitted() && $form->isValid()) {
			$entityManager->flush();

			return $this->redirectToRoute(
				'app_api_key_name_index',
				[],
				Response::HTTP_SEE_OTHER
			);
		}

		return $this->render('api_key_name/edit.html.twig', [
			'api_key_name' => $apiKeyName,
			'form' => $form,
		]);
	}

	#[Route('/{id}', name: 'app_api_key_name_delete', methods: ['POST'])]
	public function delete(
		Request $request,
		ApiKeyName $apiKeyName,
		EntityManagerInterface $entityManager
	): Response {
		if (
			$this->isCsrfTokenValid(
				'delete' . $apiKeyName->getId(),
				$request->getPayload()->getString('_token')
			)
		) {
			$entityManager->remove($apiKeyName);
			$entityManager->flush();
		}

		return $this->redirectToRoute(
			'app_api_key_name_index',
			[],
			Response::HTTP_SEE_OTHER
		);
	}
}
