<?php

namespace App\Controller\Admin;

use App\Entity\Portfolio;
use App\Form\PortfolioType;
use App\Repository\PortfolioRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/dashboard/portfolio/admin')]
final class PortfolioController extends AbstractController
{
    #[Route(name: 'app_admin_portfolio_controller_index', methods: ['GET'])]
    public function index(PortfolioRepository $portfolioRepository): Response
    {
        return $this->render('portfolio/admin/index.html.twig', [
            'portfolios' => $portfolioRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_admin_portfolio_controller_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $portfolio = new Portfolio();
        $form = $this->createForm(PortfolioType::class, $portfolio);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($portfolio);
            $entityManager->flush();

            return $this->redirectToRoute('app_admin_portfolio_controller_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('portfolio/admin/new.html.twig', [
            'portfolio' => $portfolio,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_admin_portfolio_controller_show', methods: ['GET'])]
    public function show(Portfolio $portfolio): Response
    {
        return $this->render('portfolio/admin/show.html.twig', [
            'portfolio' => $portfolio,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_admin_portfolio_controller_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Portfolio $portfolio, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(PortfolioType::class, $portfolio);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_admin_portfolio_controller_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('portfolio/admin/edit.html.twig', [
            'portfolio' => $portfolio,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_admin_portfolio_controller_delete', methods: ['POST'])]
    public function delete(Request $request, Portfolio $portfolio, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$portfolio->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($portfolio);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_admin_portfolio_controller_index', [], Response::HTTP_SEE_OTHER);
    }
}
