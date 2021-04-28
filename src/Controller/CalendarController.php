<?php

namespace App\Controller;

use App\Entity\Calendar;
use App\Entity\DateSelect;
use App\Entity\Pie;
use App\Entity\Ticker;
use App\Form\CalendarType;
use App\Repository\CalendarRepository;
use App\Service\DividendService;
use App\Service\Referer;
use DateTime;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

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
        string $orderBy = 'createdAt',
        string $sort = 'DESC',
        Referer $referer
    ): Response {
        if (!in_array($orderBy, ['paymentDate', 'ticker', 'exDividendDate', 'createdAt'])) {
            $orderBy = 'paymentDate';
        }
        if (!in_array($sort, ['asc', 'desc', 'ASC', 'DESC'])) {
            $sort = 'DESC';
        }
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

    /**
     * @Route("/new/{ticker}", name="calendar_new", methods={"GET","POST"})
     */
    function new (Request $request, ?Ticker $ticker = null, Referer $referer): Response {
        $calendar = new Calendar();
        $calendar->setTicker($ticker);
        $form = $this->createForm(CalendarType::class, $calendar);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $calendar->setSource(Calendar::SOURCE_MANUEL);
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
     * @Route("/calendarperdatetable", name="calendar_per_date_table", methods={"GET","POST"})
     */
    public function viewCalendarTable(Request $request, CalendarRepository $calendarRepository, DividendService $dividendService): Response
    {
        $year = date('Y');
        $endDate = $year . '-12-31';

        $dateSelect = new DateSelect();
        $dateSelect->setStartdate((new DateTime('now')))
            ->setEnddate((new DateTime($endDate)));

        $form = $this->createFormBuilder($dateSelect)
            ->add('startdate', DateType::class, [
                'widget' => 'single_text',
            ])
            ->add('enddate', DateType::class, [
                'widget' => 'single_text',
            ])
            ->add('pie', EntityType::class, [
                'class' => Pie::class,
                'label' => 'Pie',
                'choice_label' => 'label',
                'required' => false,
                'placeholder' => 'Please choose a Pie',
                'empty_data' => null,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('pie')
                        ->select('pie, p')
                        ->join('pie.positions', 'p')
                        ->where('(p.closed = 0 OR p.closed IS NULL)')
                        ->orderBy('pie.label', 'ASC');
                },
            ])
            ->add('save', SubmitType::class, ['label' => 'Submit'])
            ->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $dateSelect = $form->getData();
        }

        $calendars = $calendarRepository->groupByMonth($dividendService, $year, $dateSelect->getStartdate()->format('Y-m-d'), $dateSelect->getEnddate()->format('Y-m-d'), $dateSelect->getPie());

        return $this->render('calendar/view_table.html.twig', [
            'calendars' => $calendars,
            'year' => $year,
            'dateSelect' => $dateSelect,
            'form' => $form->createView(),
            'timestamp' => new DateTime()
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
