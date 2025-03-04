<?php

namespace App\Controller;

use App\Entity\MonthlySummary;
use App\Form\MonthlySummaryType;
use App\Repository\MonthlySummaryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;

#[Route(path: '/{_locale<%app.supported_locales%>}/dashboard/monthly/summary')]
final class MonthlySummaryController extends AbstractController
{
    #[Route(name: 'app_monthly_summary_index', methods: ['GET'])]
    public function index(MonthlySummaryRepository $monthlySummaryRepository): Response
    {
        return $this->render('monthly_summary/index.html.twig', [
            'monthly_summaries' => $monthlySummaryRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_monthly_summary_new', methods: ['GET', 'POST'])]
    public function create(Request $request, EntityManagerInterface $entityManager): Response
    {
        $monthlySummary = new MonthlySummary();
        $form = $this->createForm(MonthlySummaryType::class, $monthlySummary);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $uuid = Uuid::v4();
            $monthlySummary->setUuid($uuid);

            $entityManager->persist($monthlySummary);
            $entityManager->flush();

            return $this->redirectToRoute('app_monthly_summary_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('monthly_summary/new.html.twig', [
            'monthly_summary' => $monthlySummary,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_monthly_summary_show', methods: ['GET'])]
    public function show(MonthlySummary $monthlySummary): Response
    {
        return $this->render('monthly_summary/show.html.twig', [
            'monthly_summary' => $monthlySummary,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_monthly_summary_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, MonthlySummary $monthlySummary, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(MonthlySummaryType::class, $monthlySummary);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_monthly_summary_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('monthly_summary/edit.html.twig', [
            'monthly_summary' => $monthlySummary,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_monthly_summary_delete', methods: ['POST'])]
    public function delete(Request $request, MonthlySummary $monthlySummary, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$monthlySummary->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($monthlySummary);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_monthly_summary_index', [], Response::HTTP_SEE_OTHER);
    }
}
