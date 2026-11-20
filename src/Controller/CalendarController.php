<?php

namespace App\Controller;

use App\Entity\Calendar;
use App\Entity\Ticker;
use App\Form\CalendarType;
use App\Form\Model\TickerAutocomplete;
use App\Form\TickerAutocompleteType;
use App\Repository\DividendCalendarRepository;
use App\Repository\TickerRepository;
use App\Service\Referer;
use Doctrine\ORM\EntityManagerInterface;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 */
#[Route(path: '/{_locale<%app.supported_locales%>}/dashboard/calendar')]
class CalendarController extends AbstractController
{
    private const SESSION_TICKER_KEY = 'calendar_searchTicker';
    private const SORT_FIELDS = ['paymentDate', 'fullname', 'exDividendDate', 'createdAt'];
    private const DEFAULT_SORT = 'createdAt';
    private const ITEMS_PER_PAGE = 10;

    #[Route(path: '/', name: 'calendar_index', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        DividendCalendarRepository $calendarRepository,
        TickerRepository $tickerRepository,
        #[MapQueryParameter] int $page = 1,
        #[MapQueryParameter] string $sort = self::DEFAULT_SORT,
        #[MapQueryParameter] string $orderBy = 'desc'
    ): Response {
        $sort = in_array($sort, self::SORT_FIELDS, true) ? $sort : self::DEFAULT_SORT;
        $orderBy = strtolower($orderBy) === 'asc' ? 'asc' : 'desc';

        $session = $request->getSession();
        $tickerId = $session->get(self::SESSION_TICKER_KEY);
        $ticker = $tickerId !== null ? $tickerRepository->find($tickerId) : null;

        $search = new TickerAutocomplete();
        if ($ticker !== null) {
            $search->setTicker($ticker);
        }

        $form = $this->createForm(TickerAutocompleteType::class, $search, [
            'extra_options' => ['include_all_tickers' => true],
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $selectedId = $search->getTicker()?->getId();
            if ($selectedId !== null) {
                $session->set(self::SESSION_TICKER_KEY, $selectedId);
            } else {
                $session->remove(self::SESSION_TICKER_KEY);
            }

            return $this->redirectToRoute(
                'calendar_index',
                ['sort' => $sort, 'orderBy' => $orderBy],
                Response::HTTP_SEE_OTHER
            );
        }

        $pager = new Pagerfanta(new QueryAdapter($calendarRepository->getAllQuery($sort, $orderBy, $ticker)));
        $pager->setMaxPerPage(self::ITEMS_PER_PAGE);
        $pager->setNormalizeOutOfRangePages(true);
        $pager->setCurrentPage(max(1, $page));

        return $this->render('calendar/index.html.twig', [
            'form' => $form,
            'pager' => $pager,
            'thisPage' => $pager->getCurrentPage(),
            'orderBy' => $orderBy,
            'sort' => $sort,
        ]);
    }

    #[Route('/create/{ticker?}', name: 'calendar_create', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function create(
        Request $request,
        EntityManagerInterface $entityManager,
        #[MapEntity] ?Ticker $ticker,
        Referer $referer
    ): Response {
        $referer->set('calendar_index');
        $calendar = new Calendar();
        if ($ticker != null) {
            $calendar->setTicker($ticker);
        }
        $form = $this->createForm(CalendarType::class, $calendar);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($calendar);
            $entityManager->flush();

            return $this->redirectToRoute('calendar_index');
        }

        return $this->render('calendar/new.html.twig', [
            'calendar' => $calendar,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'calendar_show', methods: ['GET'])]
    public function show(#[MapEntity] Calendar $calendar): Response
    {
        return $this->render('calendar/show.html.twig', [
            'calendar' => $calendar,
        ]);
    }

    #[
        Route(
            path: '/{id}/edit',
            name: 'calendar_edit',
            methods: ['GET', 'POST']
        )
    ]
    #[IsGranted('ROLE_ADMIN')]
    public function edit(
        Request $request,
        EntityManagerInterface $entityManager,
        #[MapEntity] Calendar $calendar,
        Referer $referer
    ): Response {
        $form = $this->createForm(CalendarType::class, $calendar);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('calendar_index');
        }

        return $this->render('calendar/edit.html.twig', [
            'calendar' => $calendar,
            'form' => $form->createView(),
            'referer' => $referer->get() ?: null,
        ]);
    }

    #[Route('/delete/{id}', name: 'calendar_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(
        Request $request,
        EntityManagerInterface $entityManager,
        #[MapEntity] Calendar $calendar,
        Referer $referer
    ): Response {
        if (
            $this->isCsrfTokenValid(
                'delete' . $calendar->getId(),
                $request->request->get('_token')
            )
        ) {
            $entityManager->remove($calendar);
            $entityManager->flush();
        }

        return $this->redirectToRoute('calendar_index');
    }
}
