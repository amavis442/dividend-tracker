<?php

namespace App\Controller;

use App\Entity\Payment;
use App\Form\PaymentType;
use App\Repository\PaymentRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\PositionRepository;
use DateTime;

/**
 * @Route("/payment")
 */
class PaymentController extends AbstractController
{
    /**
     * @Route("/list/{page<\d+>?1}", name="payment_index", methods={"GET"})
     */
    public function index(PaymentRepository $paymentRepository, int $page = 1): Response
    {
        $totalDividend = $paymentRepository->getTotalDividend();

        $items = $paymentRepository->getAll($page);
        $limit = 10;
        $maxPages = ceil($items->count() / $limit);
        $thisPage = $page;

        return $this->render('payment/index.html.twig', [
            'payments' => $items->getIterator(),
            'dividends' => $totalDividend,
            'limit' => $limit,
            'maxPages' => $maxPages,
            'thisPage' => $thisPage,
            'routeName' => 'payment_index',
        ]);
    }

    /**
     * @Route("/new/{positionId<\d+>?0}", name="payment_new", methods={"GET","POST"})
     */
    public function new(Request $request, ?int $positionId, PositionRepository $positionRepository): Response
    {
        $payment = new Payment();
        if ($positionId) {
            $position = $positionRepository->find($positionId);
            $payment->setPosition($position);
        }
        $currentDate = new DateTime();
        $payment->setExDividendDate($currentDate);
        $payment->setPayDate($currentDate);

        $form = $this->createForm(PaymentType::class, $payment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $position = $payment->getPosition();    
            $payment->setTicker($position->getTicker());

            $entityManager->persist($payment);
            $entityManager->flush();

            return $this->redirectToRoute('payment_index');
        }

        return $this->render('payment/new.html.twig', [
            'payment' => $payment,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="payment_show", methods={"GET"})
     */
    public function show(Payment $payment): Response
    {
        return $this->render('payment/show.html.twig', [
            'payment' => $payment,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="payment_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Payment $payment): Response
    {
        $form = $this->createForm(PaymentType::class, $payment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $position = $payment->getPosition();    
            $payment->setTicker($position->getTicker());
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('payment_index');
        }

        return $this->render('payment/edit.html.twig', [
            'payment' => $payment,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="payment_delete", methods={"DELETE"})
     */
    public function delete(Request $request, Payment $payment): Response
    {
        if ($this->isCsrfTokenValid('delete'.$payment->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($payment);
            $entityManager->flush();
        }

        return $this->redirectToRoute('payment_index');
    }
}
