<?php

namespace App\Controller;

use App\Entity\Payment;
use App\Entity\Position;
use App\Form\PaymentType;
use App\Helper\DateHelper;
use App\Repository\CalendarRepository;
use App\Repository\PaymentRepository;
use App\Service\Referer;
use DateTime;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/dashboard/payment")
 */
class PaymentController extends AbstractController
{
    public const SEARCH_KEY = 'payment_searchCriteria';
    public const SEARCHFORM_KEY = 'payment_searchForm';
    public const INTERVAL_KEY = 'payment_interval';

    /**
     * @Route("/list/{page?1}/{tab?All}/{orderBy?payDate}/{sort?DESC}", name="payment_index", methods={"GET","POST"})
     */
    public function index(
        Request $request,
        PaymentRepository $paymentRepository,
        SessionInterface $session,
        int $page = 1,
        string $orderBy = 'payDate',
        string $sort = 'DESC',
        string $tab = 'All',
        Referer $referer
    ): Response {
        if (!in_array($orderBy, ['payDate', 'ticker'])) {
            $orderBy = 'exDividendDate';
        }
        if (!in_array($sort, ['asc', 'desc', 'ASC', 'DESC'])) {
            $sort = 'DESC';
        }

        $defaultData = $session->get(self::SEARCHFORM_KEY);
        if ($defaultData === null) {
            $defaultData['year'] = date('Y');
        }

        $currentYear = date('Y');
        for ($i = 2019; $i <= $currentYear; $i++) {
            $years[$i] = $i;
        }
        $form = $this->createFormBuilder($defaultData)
            ->add(
                'year',
                ChoiceType::class,
                ['label' => 'Year', 'choices' => $years]
            )
            ->add('month', ChoiceType::class, ['choices' => [
                '-' => 0,
                'Jan' => 1,
                'Feb' => 2,
                'Ma' => 3,
                'Apr' => 4,
                'May' => 5,
                'Jun' => 6,
                'Jul' => 7,
                'Aug' => 8,
                'Sept' => 9,
                'Oct' => 10,
                'Nov' => 11,
                'Dec' => 12,
            ]])
            ->add(
                'quator',
                ChoiceType::class,
                ['choices' => [
                    '-' => 0,
                    'Q1' => 1,
                    'Q2' => 2,
                    'Q3' => 3,
                    'Q4' => 4,
                ]]
            )
            ->add('send', SubmitType::class)
            ->getForm();

        $form->handleRequest($request);
        $data = $defaultData;
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $session->set(self::SEARCHFORM_KEY, $data);
            $page = 1;
        }

        $year = $data['year'];
        [$startDate, $endDate] = [$year . '-01-01', $year . '-12-31'];
        if (isset($data['month']) && $data['month'] !== 0) {
            [$startDate, $endDate] = (new DateHelper())->monthToDates($data['month'], $data['year']);
        }

        if (isset($data['quator']) && $data['quator'] !== 0) {
            [$startDate, $endDate] = (new DateHelper())->quaterToDates($data['quator'], $data['year']);
        }
        $totalDividend = $paymentRepository->getTotalDividend($tab, $startDate, $endDate);
        $taxes = ($totalDividend / 85) * 15;
        $searchCriteria = $session->get(self::SEARCH_KEY, '');
        $items = $paymentRepository->getAll($page, $tab, 10, $orderBy, $sort, $searchCriteria, $startDate, $endDate);
        $limit = 10;
        $maxPages = ceil($items->count() / $limit);
        $thisPage = $page;

        $referer->set('payment_index', ['page' => $page, 'tab' => $tab, 'orderBy' => $orderBy, 'sort' => $sort]);

        return $this->render('payment/index.html.twig', [
            'searchForm' => $form->createView(),
            'payments' => $items->getIterator(),
            'dividends' => $totalDividend,
            'taxes' => $taxes,
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
            'endDate' => $endDate,
        ]);
    }

    /**
     * @Route("/new/{position}/{timestamp?}", name="payment_new", methods={"GET","POST"})
     */
    function new (
        Request $request,
        position $position,
        string $timestamp = null,
        CalendarRepository $calendarRepository,
        Referer $referer
    ): Response {
        $ticker = $position->getTicker();
        if ($timestamp) {
            $year = substr($timestamp, 0, 4);
            $month = substr($timestamp, 5, 2);
        } else {
            $year = date('Y');
            $month = date('m');
        }

        $positionDividendEstimate = $calendarRepository->getDividendEstimate($position, $year);
        if (isset($positionDividendEstimate[$timestamp])) {
            $data = $positionDividendEstimate[$timestamp]['tickers'][$ticker->getTicker()];
            $amount = $data['amount'];
            $calendar = $data['calendar'];
        } else {
            $amount = $position->getAmount();
            $calendar = $calendarRepository->getLastDividend($ticker);
        }
        $payment = new Payment();
        $payment->setAmount($amount);
        $payment->setPosition($position);

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

            if ($referer->get()) {
                return $this->redirect($referer->get());
            }
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
    public function edit(
        Request $request,
        Payment $payment,
        Referer $referer
    ): Response {
        $form = $this->createForm(PaymentType::class, $payment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();
            if ($referer->get()) {
                return $this->redirect($referer->get());
            }
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
    public function delete(
        Request $request,
        Payment $payment,
        Referer $referer
    ): Response {
        if ($this->isCsrfTokenValid('delete' . $payment->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($payment);
            $entityManager->flush();
        }

        if ($referer->get()) {
            return $this->redirect($referer->get());
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

        return $this->redirectToRoute('payment_index', ['orderBy' => 'payDate', 'sort' => 'desc']);
    }
}
