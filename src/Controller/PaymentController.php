<?php

namespace App\Controller;

use App\Entity\Payment;
use App\Entity\Ticker;
use App\Form\PaymentType;
use App\Repository\PaymentRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Helper\DateHelper;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use DateTime;
use App\Repository\CalendarRepository;
use App\Repository\TickerRepository;

/**
 * @Route("/dashboard/payment")
 */
class PaymentController extends AbstractController
{
    public const SEARCH_KEY = 'payment_searchCriteria';
    public const INTERVAL_KEY = 'payment_interval';

    /**
     * @Route("/list/{page}/{tab}/{orderBy}/{sort}", name="payment_index", methods={"GET"})
     */
    public function index(
        PaymentRepository $paymentRepository,
        SessionInterface $session,
        int $page = 1,
        string $orderBy = 'payDate',
        string $sort = 'DESC',
        string $tab = 'All'
    ): Response {
        if (!in_array($orderBy, ['payDate', 'ticker'])) {
            $orderBy = 'exDividendDate';
        }
        if (!in_array($sort, ['asc', 'desc', 'ASC', 'DESC'])) {
            $sort = 'DESC';
        }

        $totalDividend = $paymentRepository->getTotalDividend($tab);

        $searchCriteria = $session->get(self::SEARCH_KEY, '');
        $items = $paymentRepository->getAll($page, $tab, 10, $orderBy, $sort, $searchCriteria);
        $limit = 10;
        $maxPages = ceil($items->count() / $limit);
        $thisPage = $page;

        [$startDate, $endDate] = (new DateHelper())->getInterval($tab);
        if ($tab === 'All') {
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
            'tab' => $tab,
            'searchCriteria' => $searchCriteria ?? '',
            'routeName' => 'payment_index',
            'searchPath' => 'payment_search',
            'startDate' => $startDate,
            'endDate' => $endDate
        ]);
    }

    /**
     * @Route("/new/{ticker}", name="payment_new", methods={"GET","POST"})
     */
    public function new(Request $request, CalendarRepository $calendarRepository, TickerRepository $tickerRepository, Ticker $ticker): Response
    {
        $units  = $tickerRepository->getActiveUnits($ticker);
        
        $payment = new Payment();
        $payment->setTicker($ticker);
        $payment->setStocks($units);
        $calendar = $calendarRepository->getLastDividend($ticker);
        if ($calendar) {
            $payment->setCalendar($calendar);
            $payment->setDividend($calendar->getCashAmount());
        }
       $payment->setPayDate(new DateTime());

        $form = $this->createForm(PaymentType::class, $payment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $payment->setTicker($ticker);

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

        return $this->redirectToRoute('payment_index',['orderBy' => 'payDate','sort' => 'desc']);
    }
}
