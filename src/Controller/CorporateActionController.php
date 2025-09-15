<?php

namespace App\Controller;

use App\Entity\CorporateAction;
use App\Form\CorporateActionType;
use App\Repository\CorporateActionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/{_locale<%app.supported_locales%>}/dashboard/corporate/action')]
final class CorporateActionController extends AbstractController
{
    #[Route(name: 'app_corporate_action_index', methods: ['GET'])]
    public function index(CorporateActionRepository $corporateActionRepository): Response
    {
        return $this->render('corporate_action/index.html.twig', [
            'corporate_actions' => $corporateActionRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_corporate_action_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $corporateAction = new CorporateAction();
        $form = $this->createForm(CorporateActionType::class, $corporateAction);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($corporateAction);
            $entityManager->flush();

            return $this->redirectToRoute('app_corporate_action_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('corporate_action/new.html.twig', [
            'corporate_action' => $corporateAction,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_corporate_action_show', methods: ['GET'])]
    public function show(CorporateAction $corporateAction): Response
    {
        return $this->render('corporate_action/show.html.twig', [
            'corporate_action' => $corporateAction,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_corporate_action_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, CorporateAction $corporateAction, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CorporateActionType::class, $corporateAction);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_corporate_action_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('corporate_action/edit.html.twig', [
            'corporate_action' => $corporateAction,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_corporate_action_delete', methods: ['POST'])]
    public function delete(Request $request, CorporateAction $corporateAction, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$corporateAction->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($corporateAction);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_corporate_action_index', [], Response::HTTP_SEE_OTHER);
    }
}
