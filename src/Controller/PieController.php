<?php

namespace App\Controller;

use App\Entity\Pie;
use App\Form\PieType;
use App\Repository\PieRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/dashboard/pie")
 */
class PieController extends AbstractController
{
    /**
     * @Route("/", name="pie_index", methods={"GET"})
     */
    public function index(PieRepository $pieRepository): Response
    {
        return $this->render('pie/index.html.twig', [
            'pies' => $pieRepository->findAll(),
        ]);
    }

    /**
     * @Route("/create", name="pie_new", methods={"GET","POST"})
     */
    public function create(Request $request): Response
    {
        $pie = new Pie();
        $form = $this->createForm(PieType::class, $pie);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($pie);
            $entityManager->flush();

            return $this->redirectToRoute('pie_index');
        }

        return $this->render('pie/new.html.twig', [
            'pie' => $pie,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="pie_show", methods={"GET"})
     */
    public function show(Pie $pie): Response
    {
        return $this->render('pie/show.html.twig', [
            'pie' => $pie,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="pie_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Pie $pie): Response
    {
        $form = $this->createForm(PieType::class, $pie);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('pie_index');
        }

        return $this->render('pie/edit.html.twig', [
            'pie' => $pie,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="pie_delete", methods={"DELETE"})
     */
    public function delete(Request $request, Pie $pie): Response
    {
        if ($this->isCsrfTokenValid('delete' . $pie->getId(), $request->request->get('_token'))) {
            $positions = $pie->getPositions();
            foreach ($positions as $position) {
                $position->removePie($pie);
            }
            $transactions = $pie->getTransactions();
            foreach ($transactions as $transaction) {
                $transaction->setPie(null);
            }

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($pie);
            $entityManager->flush();
        }

        return $this->redirectToRoute('pie_index');
    }
}
