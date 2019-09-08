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
use App\Helper\DateHelper;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * @Route("/dashboard/payment")
 */
class PaymentController extends AbstractController
{
    public const SEARCH_KEY = 'payment_searchCriteria';
    public const INTERVAL_KEY = 'payment_interval';

    /**
     * @Route("/list/{page}/{orderBy}/{sort}/{interval}", name="payment_index", methods={"GET"})
     */
    public function index(
        PaymentRepository $paymentRepository,
        SessionInterface $session,
        int $page = 1,
        string $orderBy = 'payDate',
        string $sort = 'DESC',
        int $interval = 0
    ): Response {
        if (!in_array($orderBy, ['payDate', 'ticker'])) {
            $orderBy = 'exDividendDate';
        }
        if (!in_array($sort, ['asc', 'desc', 'ASC', 'DESC'])) {
            $sort = 'DESC';
        }

        $totalDividend = $paymentRepository->getTotalDividend($interval);

        $searchCriteria = $session->get(self::SEARCH_KEY, '');
        $items = $paymentRepository->getAll($page, 10, $orderBy, $sort, $searchCriteria, $interval);
        $limit = 10;
        $maxPages = ceil($items->count() / $limit);
        $thisPage = $page;

        if ($page > 1) {
            $interval = $session->get(self::INTERVAL_KEY, $interval);
        }
        if ($page == 1) {
            $session->set(self::INTERVAL_KEY, $interval);
        }
        [$startDate, $endDate] = (new DateHelper())->getInterval($interval);
        if ($interval === 0)
        {
            $startDate = null;
        } 
        
        return $this->render('payment/index.html.twig', [
            'payments' => $items->getIterator(),
            'dividends' => $totalDividend,
            'limit' => $limit,
            'maxPages' => $maxPages,
            'thisPage' => $thisPage,
            'order' => $orderBy,
            'sort' => $sort,
            'searchCriteria' => $searchCriteria ?? '',
            'routeName' => 'payment_index',
            'searchPath' => 'payment_search',
            'startDate' => $startDate,
            'endDate' => $endDate,
            'interval' => $interval,
        ]);
    }

    /**
     * @Route("/new/{positionId<\d+>?0}", name="payment_new", methods={"GET","POST"})
     */
    public function new(Request $request, ?int $positionId, PositionRepository $positionRepository): Response
    {
        $tickerId = 0;
        $payment = new Payment();
        if ($positionId) {
            $position = $positionRepository->find($positionId);
            $payment->setPosition($position);
            $tickerId = $position->getTicker()->getId();
        }

        $form = $this->createForm(PaymentType::class, $payment, ['tickerId' => $tickerId]);
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
        if ($this->isCsrfTokenValid('delete' . $payment->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($payment);
            $entityManager->flush();
        }

        return $this->redirectToRoute('payment_index');
    }

    /**
     * @Route("/search", name="payment_search", methods={"POST"})
     */
    public function search(Request $request, SessionInterface $session): Response
    {
        $searchCriteria = $request->request->get('searchCriteria');
        $session->set(self::SEARCH_KEY, $searchCriteria);

        return $this->redirectToRoute('payment_index');
    }
}
