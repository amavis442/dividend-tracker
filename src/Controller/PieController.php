<?php

namespace App\Controller;

use App\Entity\Pie;
use App\Form\PieType;
use App\Repository\PieRepository;
use App\Repository\PositionRepository;
use App\Repository\TransactionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/{_locale<%app.supported_locales%>}/dashboard/pie')]
class PieController extends AbstractController
{
    #[Route(path: '/', name: 'pie_index', methods: ['GET'])]
    public function index(PieRepository $pieRepository): Response
    {
        return $this->render('pie/index.html.twig', [
            'pies' => $pieRepository->findAll(),
        ]);
    }

    #[Route(path: '/create', name: 'pie_new', methods: ['GET', 'POST'])]
    public function create(Request $request, EntityManagerInterface $entityManager): Response
    {
        $pie = new Pie();
        $form = $this->createForm(PieType::class, $pie);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($pie);
            $entityManager->flush();

            return $this->redirectToRoute('pie_index');
        }

        return $this->render('pie/new.html.twig', [
            'pie' => $pie,
            'form' => $form->createView(),
        ]);
    }

    #[Route(path: '/{id}', name: 'pie_show', methods: ['GET'])]
    public function show(Pie $pie): Response
    {
        return $this->render('pie/show.html.twig', [
            'pie' => $pie,
        ]);
    }

    #[Route(path: '/{id}/edit', name: 'pie_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, EntityManagerInterface $entityManager, Pie $pie): Response
    {
        $form = $this->createForm(PieType::class, $pie);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('pie_index');
        }

        return $this->render('pie/edit.html.twig', [
            'pie' => $pie,
            'form' => $form->createView(),
        ]);
    }

    #[Route(path: '/delete/{id}', name: 'pie_delete', methods: ['POST', 'DELETE'])]
    public function delete(Request $request,
        EntityManagerInterface $entityManager,
        Pie $pie,
        TransactionRepository $transactionRepository,
        ): Response
    {
        if ($this->isCsrfTokenValid('delete' . $pie->getId(), $request->request->get('_token'))) {
            $entityManager->getConnection()->executeQuery('DELETE FROM pie_position WHERE pie_id = :pie', ['pie'=>$pie->getId()]);
            $transactionRepository->removePie($pie);

            $entityManager->remove($pie);
            $entityManager->flush();
        }

        return $this->redirectToRoute('pie_index');
    }
}
