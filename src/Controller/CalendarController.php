<?php

namespace App\Controller;

use App\Entity\Calendar;
use App\Form\CalendarType;
use App\Repository\CalendarRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use App\Entity\Ticker;
use App\Service\DividendService;
use App\Service\Referer;

/**
 * @Route("/dashboard/calendar")
 */
class CalendarController extends AbstractController
{
    public const SEARCH_KEY = 'calendar_searchCriteria';

    /**
     * @Route("/list/{page}/{orderBy}/{sort}", name="calendar_index", methods={"GET"})
     */
    public function index(
        CalendarRepository $calendarRepository,
        SessionInterface $session,
        int $page = 1,
        string $orderBy = 'paymentDate',
        string $sort = 'DESC',
        Referer $referer
    ): Response {
        if (!in_array($orderBy, ['paymentDate', 'ticker', 'exDividendDate'])) {
            $orderBy = 'paymentDate';
        }
        if (!in_array($sort, ['asc', 'desc', 'ASC', 'DESC'])) {
            $sort = 'DESC';
        }
        $searchCriteria = $session->get(self::SEARCH_KEY, '');
        $items = $calendarRepository->getAll($page, 10, $orderBy, $sort, $searchCriteria);
        $limit = 10;
        $maxPages = ceil($items->count() / $limit);
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
            'searchPath' => 'calendar_search'
        ]);
    }

    /**
     * @Route("/new/{ticker}", name="calendar_new", methods={"GET","POST"})
     */
    public function new(Request $request, ?Ticker $ticker = null, Referer $referer): Response
    {
        $calendar = new Calendar();
        $calendar->setTicker($ticker);
        $form = $this->createForm(CalendarType::class, $calendar);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($calendar);
            $entityManager->flush();

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

    /**
     * @Route("/calendarperdate", name="calendar_per_date", methods={"GET"})
     */
    public function viewCalendar(CalendarRepository $calendarRepository, DividendService $dividendService)
    {
        $year = date('Y');
        $calendar = $calendarRepository->groupByMonth($year);
        return $this->render('calendar/view.html.twig', [
            'calendar' => $calendar,
            'year' => $year,
            'dividendService' => $dividendService,
        ]);
    }

    /**
     * @Route("/{id}", name="calendar_show", methods={"GET"})
     */
    public function show(Calendar $calendar): Response
    {
        return $this->render('calendar/show.html.twig', [
            'calendar' => $calendar,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="calendar_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Calendar $calendar, Referer $referer): Response
    {       
        $form = $this->createForm(CalendarType::class, $calendar);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            
            $this->getDoctrine()->getManager()->flush();
            
            if ($referer->get()) {
                return $this->redirect($referer->get());
            }
            return $this->redirectToRoute('calendar_index');
        }

        return $this->render('calendar/edit.html.twig', [
            'calendar' => $calendar,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="calendar_delete", methods={"DELETE"})
     */
    public function delete(Request $request, Calendar $calendar, Referer $referer): Response
    {
        if ($this->isCsrfTokenValid('delete' . $calendar->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($calendar);
            $entityManager->flush();
        }

        if ($referer->get()) {
            return $this->redirect($referer->get());
        }
        return $this->redirectToRoute('calendar_index');
    }

    /**
     * @Route("/search", name="calendar_search", methods={"POST"})
     */
    public function search(Request $request, SessionInterface $session): Response
    {
        $searchCriteria = $request->request->get('searchCriteria');
        $session->set(self::SEARCH_KEY, $searchCriteria);

        return $this->redirectToRoute('calendar_index');
    }


}
