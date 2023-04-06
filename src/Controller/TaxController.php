<?php

namespace App\Controller;

use App\Entity\Tax;
use App\Form\TaxType;
use App\Repository\TaxRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/dashboard/tax')]
class TaxController extends AbstractController
{
    #[Route(path: '/', name: 'tax_index', methods: ['GET'])]
    public function index(TaxRepository $taxRepository): Response
    {
        return $this->render('tax/index.html.twig', [
            'taxes' => $taxRepository->findAll(),
        ]);
    }

    #[Route(path: '/create', name: 'tax_new', methods: ['GET', 'POST'])]
    public function create(Request $request, EntityManagerInterface $entityManager): Response
    {
        $tax = new Tax();
        $form = $this->createForm(TaxType::class, $tax);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($tax);
            $entityManager->flush();

            return $this->redirectToRoute('tax_index');
        }

        return $this->render('tax/new.html.twig', [
            'tax' => $tax,
            'form' => $form->createView(),
        ]);
    }

    #[Route(path: '/{id}', name: 'tax_show', methods: ['GET'])]
    public function show(Tax $tax): Response
    {
        return $this->render('tax/show.html.twig', [
            'tax' => $tax,
        ]);
    }

    #[Route(path: '/{id}/edit', name: 'tax_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, EntityManagerInterface $entityManager, Tax $tax): Response
    {
        $form = $this->createForm(TaxType::class, $tax);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('tax_index');
        }

        return $this->render('tax/edit.html.twig', [
            'tax' => $tax,
            'form' => $form->createView(),
        ]);
    }

    #[Route(path: '/{id}', name: 'tax_delete', methods: ['DELETE'])]
    public function delete(Request $request, EntityManagerInterface $entityManager, Tax $tax): Response
    {
        if ($this->isCsrfTokenValid('delete' . $tax->getId(), $request->request->get('_token'))) {
            $entityManager->remove($tax);
            $entityManager->flush();
        }

        return $this->redirectToRoute('tax_index');
    }
}
