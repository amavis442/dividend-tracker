<?php

namespace App\Controller;

use ApiPlatform\Core\Filter\Validator\Enum;
use App\Entity\Payment;
use App\Entity\Position;
use App\Form\PaymentType;
use App\Helper\DateHelper;
use App\Repository\CalendarRepository;
use App\Repository\PaymentRepository;
use App\Service\Referer;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/dashboard/payment')]
class PaymentController extends AbstractController
{
    public const SEARCH_KEY = 'payment_searchCriteria';
    public const SEARCHFORM_KEY = 'payment_searchForm';
    public const INTERVAL_KEY = 'payment_interval';

    #[Route(path: '/list/{page?1}/{orderBy?payDate}/{sort?DESC}', name: 'payment_index', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        PaymentRepository $paymentRepository,
        Referer $referer,
        int $page = 1,
        string $orderBy = 'payDate',
        string $sort = 'DESC'
    ): Response {
        if (!in_array($orderBy, ['payDate', 'ticker'])) {
            $orderBy = 'exDividendDate';
        }
        if (!in_array($sort, ['asc', 'desc', 'ASC', 'DESC'])) {
            $sort = 'DESC';
        }

        $defaultData = $request->getSession()->get(self::SEARCHFORM_KEY);
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
                ['label' => 'Year', 'choices' => $years, 'choice_translation_domain' => false,]
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
            ], 'choice_translation_domain' => false,])
            ->add(
                'quator',
                ChoiceType::class,
                ['choices' => [
                    '-' => 0,
                    'Q1' => 1,
                    'Q2' => 2,
                    'Q3' => 3,
                    'Q4' => 4,
                ], 'choice_translation_domain' => false,]
            )
            ->add('submit', SubmitType::class)
            ->getForm();

        $form->handleRequest($request);
        $data = $defaultData;
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $request->getSession()->set(self::SEARCHFORM_KEY, $data);
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
        $totalDividend = $paymentRepository->getTotalDividend($startDate . " 00:00:00", $endDate . " 23:59:59");
        $taxes = ($totalDividend / 85) * 15;
        $searchCriteria = $request->getSession()->get(self::SEARCH_KEY, '');
        $items = $paymentRepository->getAll($page, 10, $orderBy, $sort, $searchCriteria, $startDate, $endDate);
        $limit = 10;
        $maxPages = ceil($items->count() / $limit);
        $thisPage = $page;

        $referer->set('payment_index', ['page' => $page, 'orderBy' => $orderBy, 'sort' => $sort]);

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
            'searchCriteria' => $searchCriteria ?? '',
            'routeName' => 'payment_index',
            'searchPath' => 'payment_search',
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);
    }

    #[Route(path: '/create/{position}/{timestamp?}', name: 'payment_new', methods: ['GET', 'POST'])]
    public function create(
        Request $request,
        EntityManagerInterface $entityManager,
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

    #[Route(path: '/{id}', name: 'payment_show', methods: ['GET'])]
    public function show(Payment $payment): Response
    {
        return $this->render('payment/show.html.twig', [
            'payment' => $payment,
        ]);
    }

    #[Route(path: '/{id}/edit', name: 'payment_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        EntityManagerInterface $entityManager,
        Payment $payment,
        Referer $referer
    ): Response {
        $form = $this->createForm(PaymentType::class, $payment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
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

    #[Route(path: '/delete/{id}', name: 'payment_delete', methods: ['POST', 'DELETE'])]
    public function delete(
        Request $request,
        EntityManagerInterface $entityManager,
        Payment $payment,
        Referer $referer
    ): Response {
        if ($this->isCsrfTokenValid('delete' . $payment->getId(), $request->request->get('_token'))) {
            $entityManager->remove($payment);
            $entityManager->flush();
        }

        if ($referer->get()) {
            return $this->redirect($referer->get());
        }
        return $this->redirectToRoute('payment_index');
    }

    #[Route(path: '/search', name: 'payment_search', methods: ['POST'])]
    public function search(Request $request): Response
    {
        $searchCriteria = $request->request->get('searchCriteria');
        $request->getSession()->set(self::SEARCH_KEY, $searchCriteria);

        return $this->redirectToRoute('payment_index', ['orderBy' => 'payDate', 'sort' => 'desc']);
    }
}
