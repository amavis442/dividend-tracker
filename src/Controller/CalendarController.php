<?php

namespace App\Controller;

use App\Entity\Calendar;
use App\Entity\DateSelect;
use App\Entity\Ticker;
use App\Form\CalendarDividendType;
use App\Form\CalendarType;
use App\Model\PortfolioModel;
use App\Repository\CalendarRepository;
use App\Service\DividendService;
use App\Service\Referer;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/dashboard/calendar')]
class CalendarController extends AbstractController
{
    public const SEARCH_KEY = 'calendar_searchCriteria';

    #[Route(path: '/list/{page}/{orderBy}/{sort}', name: 'calendar_index', methods: ['GET'])]
    public function index(
        Request $request,
        CalendarRepository $calendarRepository,
        Referer $referer,
        int $page = 1,
        string $orderBy = 'paymentDate',
        string $sort = 'DESC'
    ): Response {
        if (!in_array($orderBy, ['paymentDate', 'ticker', 'exDividendDate', 'createdAt'])) {
            $orderBy = 'paymentDate';
        }
        if (!in_array($sort, ['asc', 'desc', 'ASC', 'DESC'])) {
            $sort = 'DESC';
        }
        $session = $request->getSession();
        $searchCriteria = $session->get(self::SEARCH_KEY, '');
        $items = $calendarRepository->getAll($page, 10, $orderBy, $sort, $searchCriteria);
        $limit = 10;
        $maxPages = ceil($items->count() / $limit) > 10 ? 10 : ceil($items->count() / $limit);
        $thisPage = $page;

        $referer->set('calendar_index', ['page' => $page, 'orderBy' => $orderBy, 'sort' => $sort]);

        return $this->render('calendar/index.html.twig', [
            'calendars' => $items,
            'limit' => $limit,
            'maxPages' => $maxPages,
            'thisPage' => $thisPage,
            'order' => $orderBy,
            'sort' => $sort,
            'routeName' => 'calendar_index',
            'searchCriteria' => $searchCriteria ?? '',
            'searchPath' => 'calendar_search',
        ]);
    }

    #[Route(path: '/create/{ticker?}', name: 'calendar_new', methods: ['GET', 'POST'])]
    public function create(
        Request $request,
        EntityManagerInterface $entityManager,
        ?Ticker $ticker,
        Referer $referer
    ): Response {
        $calendar = new Calendar();
        if ($ticker != null) {
            $calendar->setTicker($ticker);
        }
        $form = $this->createForm(CalendarType::class, $calendar);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $calendar->setSource(Calendar::SOURCE_MANUEL);
            $entityManager->persist($calendar);
            $entityManager->flush();

            PortfolioModel::clearCache();

            if ($referer->get()) {
                return $this->redirect($referer->get());
            }
            return $this->redirectToRoute('calendar_index');
        }

        return $this->render('calendar/new.html.twig', [
            'calendar' => $calendar,
            'form' => $form->createView(),
        ]);
    }

    #[Route(path: '/calendarperdatetable', name: 'calendar_per_date_table', methods: ['GET', 'POST'])]
    public function viewCalendarTable(
        Request $request,
        CalendarRepository $calendarRepository,
        DividendService $dividendService
    ): Response {
        $year = date('Y');
        $endDate = $year . '-12-31';

        $dateSelect = new DateSelect();
        $dateSelect->setStartdate((new DateTime('now')))
            ->setEnddate((new DateTime($endDate)));
        $form = $this->createForm(CalendarDividendType::class, $dateSelect);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $dateSelect = $form->getData();
        }

        $calendars = $calendarRepository->groupByMonth(
            $dividendService,
            (int) $year,
            $dateSelect->getStartdate()->format('Y-m-d'),
            $dateSelect->getEnddate()->format('Y-m-d'),
            $dateSelect->getPie()
        );

        return $this->render('calendar/view_table.html.twig', [
            'calendars' => $calendars,
            'year' => $year,
            'dateSelect' => $dateSelect,
            'form' => $form->createView(),
            'timestamp' => new DateTime(),
        ]);
    }

    #[Route(path: '/{id}', name: 'calendar_show', methods: ['GET'])]
    public function show(Calendar $calendar): Response
    {
        return $this->render('calendar/show.html.twig', [
            'calendar' => $calendar,
        ]);
    }

    #[Route(path: '/{id}/edit', name: 'calendar_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        EntityManagerInterface $entityManager,
        Calendar $calendar,
        Referer $referer
    ): Response {
        $form = $this->createForm(CalendarType::class, $calendar);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($calendar);
            $entityManager->flush();

            PortfolioModel::clearCache();

            if ($referer->get()) {
                return $this->redirect($referer->get());
            }
            return $this->redirectToRoute('calendar_index');
        }

        return $this->render('calendar/edit.html.twig', [
            'calendar' => $calendar,
            'form' => $form->createView(),
            'referer' => $referer->get() ?: null
        ]);
    }

    #[Route(path: '/delete/{id}', name: 'calendar_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        EntityManagerInterface $entityManager,
        Calendar $calendar,
        Referer $referer
    ): Response {
        if ($this->isCsrfTokenValid('delete' . $calendar->getId(), $request->request->get('_token'))) {
            if ($calendar->getPayments()->isEmpty()) {
                $entityManager->remove($calendar);
                $entityManager->flush();
            } else {
                $this->addFlash('notice', 'Can not remove calendar because it has payments linked to it.');
            }
        }

        if ($referer->get()) {
            return $this->redirect($referer->get());
        }
        return $this->redirectToRoute('calendar_index');
    }

    #[Route(path: '/search', name: 'calendar_search', methods: ['POST'])]
    public function search(
        Request $request
    ): Response {
        $searchCriteria = $request->request->get('searchCriteria');
        $request->getSession()->set(self::SEARCH_KEY, $searchCriteria);

        return $this->redirectToRoute('calendar_index');
    }
}
