<?php

namespace App\Controller;

use App\Entity\Constants;
use App\Entity\DateIntervalSelect;
use App\Entity\Payment;
use App\Entity\Position;
use App\Form\DateIntervalFormType;
use App\Form\PaymentType;
use App\Helper\DateHelper;
use App\Model\PortfolioModel;
use App\Repository\CalendarRepository;
use App\Repository\PaymentRepository;
use App\Service\Referer;
use App\Traits\TickerAutocompleteTrait;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: "/dashboard/payment")]
class PaymentController extends AbstractController
{
    public const SEARCH_KEY = "payment_searchCriteria";
    public const SEARCHFORM_KEY = "payment_searchForm";
    public const INTERVAL_KEY = "payment_interval";

    #[Route(path: "/list/{page?1}", name: "payment_index", methods: ["GET"])]
    public function index(
        Request $request,
        PaymentRepository $paymentRepository,
        Referer $referer,
        int $page = 1
    ): Response {
        $orderBy = "payDate";
        $sort = "DESC";

        $dateIntervalSelect = new DateIntervalSelect();
        $dateIntervalSelect->setYear((int) date("Y"));

        $form = $this->createForm(
            DateIntervalFormType::class,
            $dateIntervalSelect,
            [
                "startYear" => 2019,
                "extra_options" => ["include_all_tickers" => false],
            ]
        );

        $ticker = null;
        $year = (int) date("Y");
        $month = null;
        $qautor = null;
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $page = 1;
            $year = $dateIntervalSelect->getYear();
            $month = $dateIntervalSelect->getMonth();
            $qautor = $dateIntervalSelect->getQuator();
            $ticker = $dateIntervalSelect->getTicker();
        }

        [$startDate, $endDate] = [$year . "-01-01", $year . "-12-31"];
        if ($month && $month !== 0) {
            [$startDate, $endDate] = (new DateHelper())->monthToDates(
                $month,
                $year
            );
        }

        if ($qautor) {
            [$startDate, $endDate] = (new DateHelper())->quaterToDates(
                $qautor,
                $year
            );
        }
        $totalDividend = $paymentRepository->getTotalDividend(
            $startDate . " 00:00:00",
            $endDate . " 23:59:59",
            $ticker
        );

        // TODO: Make this dynamic because not all stocks have 15% dividend tax
        $taxes = ($totalDividend / (100 - Constants::TAX)) * Constants::TAX;

        //$searchCriteria = $request->getSession()->get(self::SEARCH_KEY, "");
        $items = $paymentRepository->getAll(
            $page,
            10,
            $orderBy,
            $sort,
            $ticker,
            $startDate,
            $endDate
        );
        $limit = 10;
        $maxPages = ceil($items->count() / $limit);
        $thisPage = $page;

        $referer->set("payment_index", [
            "page" => $page,
            "orderBy" => $orderBy,
            "sort" => $sort,
        ]);

        return $this->render("payment/index.html.twig", [
            "searchForm" => $form,
            "payments" => $items,
            "dividends" => $totalDividend,
            "taxes" => $taxes,
            "limit" => $limit,
            "maxPages" => $maxPages,
            "thisPage" => $thisPage,
            "order" => $orderBy,
            "sort" => $sort,
            "searchCriteria" => $searchCriteria ?? "",
            "routeName" => "payment_index",
            "searchPath" => "payment_search",
            "startDate" => $startDate,
            "endDate" => $endDate,
        ]);
    }

    #[
        Route(
            path: "/create/{position}/{timestamp?}",
            name: "payment_new",
            methods: ["GET", "POST"]
        )
    ]
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
            $year = (int) substr($timestamp, 0, 4);
            $month = (int) substr($timestamp, 5, 2);
        } else {
            $year = (int) date("Y");
            $month = (int) date("m");
        }

        $positionDividendEstimate = $calendarRepository->getDividendEstimate(
            $position,
            $year
        );
        if (isset($positionDividendEstimate[$timestamp])) {
            $data =
                $positionDividendEstimate[$timestamp]["tickers"][
                    $ticker->getSymbol()
                ];
            $amount = $data["amount"];
            $calendar = $data["calendar"];
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

            PortfolioModel::clearCache();

            if ($referer->get()) {
                return $this->redirect($referer->get());
            }
            return $this->redirectToRoute("payment_index");
        }

        return $this->render("payment/new.html.twig", [
            "payment" => $payment,
            "form" => $form->createView(),
        ]);
    }

    #[Route(path: "/{id}", name: "payment_show", methods: ["GET"])]
    public function show(Payment $payment): Response
    {
        return $this->render("payment/show.html.twig", [
            "payment" => $payment,
        ]);
    }

    #[Route(path: "/{id}/edit", name: "payment_edit", methods: ["GET", "POST"])]
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

            PortfolioModel::clearCache();

            if ($referer->get()) {
                return $this->redirect($referer->get());
            }
            return $this->redirectToRoute("payment_index");
        }

        return $this->render("payment/edit.html.twig", [
            "payment" => $payment,
            "form" => $form->createView(),
        ]);
    }

    #[
        Route(
            path: "/delete/{id}",
            name: "payment_delete",
            methods: ["POST", "DELETE"]
        )
    ]
    public function delete(
        Request $request,
        EntityManagerInterface $entityManager,
        Payment $payment,
        Referer $referer
    ): Response {
        if (
            $this->isCsrfTokenValid(
                "delete" . $payment->getId(),
                $request->request->get("_token")
            )
        ) {
            $entityManager->remove($payment);
            $entityManager->flush();
        }

        if ($referer->get()) {
            return $this->redirect($referer->get());
        }
        return $this->redirectToRoute("payment_index");
    }

    #[Route(path: "/search", name: "payment_search", methods: ["POST"])]
    public function search(Request $request): Response
    {
        $searchCriteria = $request->request->get("searchCriteria");
        $request->getSession()->set(self::SEARCH_KEY, $searchCriteria);

        return $this->redirectToRoute("payment_index", [
            "orderBy" => "payDate",
            "sort" => "desc",
        ]);
    }
}
