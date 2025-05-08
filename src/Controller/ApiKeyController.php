<?php

namespace App\Controller;

use App\Entity\ApiKey;
use App\Form\ApiKeyType;
use App\Repository\ApiKeyRepository;
use Doctrine\ORM\EntityManagerInterface;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/{_locale<%app.supported_locales%>}/dashboard/user/apikeys')]
final class ApiKeyController extends AbstractController
{
	#[Route(name: 'app_api_key_index', methods: ['GET'])]
	public function index(
		ApiKeyRepository $apiKeyRepository,
		#[MapQueryParameter] int $page = 1
	): Response {
		$queryBuilder = $apiKeyRepository->getQueryBuilderFindByAll();
		$adapter = new QueryAdapter($queryBuilder);
		$pager = new Pagerfanta($adapter);
		$pager->setMaxPerPage(10);
		$pager->setCurrentPage($page);

		return $this->render('api_key/index.html.twig', [
			'pager' => $pager,
			'title' => 'Api Key',
		]);
	}

	#[Route('/new', name: 'app_api_key_new', methods: ['GET', 'POST'])]
	public function new(
		Request $request,
		EntityManagerInterface $entityManager
	): Response {
		$apiKey = new ApiKey();
		$form = $this->createForm(ApiKeyType::class, $apiKey);
		$form->handleRequest($request);

		if ($form->isSubmitted() && $form->isValid()) {
			$entityManager->persist($apiKey);
			$entityManager->flush();

			return $this->redirectToRoute(
				'app_api_key_index',
				[],
				Response::HTTP_SEE_OTHER
			);
		}

		return $this->render('api_key/new.html.twig', [
			'api_key' => $apiKey,
			'form' => $form,
		]);
	}

	#[Route('/{id}', name: 'app_api_key_show', methods: ['GET'])]
	public function show(ApiKey $apiKey): Response
	{
		return $this->render('api_key/show.html.twig', [
			'api_key' => $apiKey,
		]);
	}

	#[Route('/{id}/edit', name: 'app_api_key_edit', methods: ['GET', 'POST'])]
	public function edit(
		Request $request,
		ApiKey $apiKey,
		EntityManagerInterface $entityManager
	): Response {
		$form = $this->createForm(ApiKeyType::class, $apiKey);
		$form->handleRequest($request);

		if ($form->isSubmitted() && $form->isValid()) {
			$entityManager->flush();

			return $this->redirectToRoute(
				'app_api_key_index',
				[],
				Response::HTTP_SEE_OTHER
			);
		}

		return $this->render('api_key/edit.html.twig', [
			'api_key' => $apiKey,
			'form' => $form,
		]);
	}

	#[Route('/{id}', name: 'app_api_key_delete', methods: ['POST'])]
	public function delete(
		Request $request,
		ApiKey $apiKey,
		EntityManagerInterface $entityManager
	): Response {
		if (
			$this->isCsrfTokenValid(
				'delete' . $apiKey->getId(),
				$request->getPayload()->getString('_token')
			)
		) {
			$entityManager->remove($apiKey);
			$entityManager->flush();
		}

		return $this->redirectToRoute(
			'app_api_key_index',
			[],
			Response::HTTP_SEE_OTHER
		);
	}
}
