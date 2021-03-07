<?php

namespace App\Controller;

use App\Entity\Position;
use App\Entity\Ticker;
use App\Form\PositionType;
use App\Repository\PositionRepository;
use App\Service\PositionService;
use App\Service\Referer;
use App\Service\Summary;
use DateTime;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/dashboard/position")
 */
class PositionController extends AbstractController
{
    public const SEARCH_KEY = 'position_searchCriteria';

    /**
     * @Route("/list/{page}/{tab}/{orderBy}/{sort}/{status}", name="position_index", methods={"GET"})
     */
    public function index(
        Summary $summary,
        PositionRepository $positionRepository,
        SessionInterface $session,
        int $page = 1,
        string $tab = 'All',
        string $orderBy = 'ticker',
        string $sort = 'asc',
        int $status = PositionRepository::CLOSED,
        Referer $referer
    ): Response {
        if (!in_array($orderBy, ['profit'])) {
            $order = 'p.' . $orderBy;
        }
        if (!in_array($orderBy, ['ticker'])) {
            $order = 't.ticker';
        }

        if (!in_array($sort, ['asc', 'desc', 'ASC', 'DESC'])) {
            $sort = 'asc';
        }

        $referer->set('position_index', ['status' => $status]);

        [$numActivePosition, $numTickers, $profit, $totalDividend, $allocated] = $summary->getSummary();

        $searchCriteria = $session->get(self::SEARCH_KEY, '');
        $items = $positionRepository->getAll($page, 10, $order, $sort, $searchCriteria, $status);
        $limit = 10;
        $maxPages = ceil($items->count() / $limit);
        $thisPage = $page;

        return $this->render('position/index.html.twig', [
            'positions' => $items->getIterator(),
            'limit' => $limit,
            'maxPages' => $maxPages,
            'thisPage' => $thisPage,
            'order' => $orderBy,
            'sort' => $sort,
            'searchCriteria' => $searchCriteria ?? '',
            'status' => $status,
            'routeName' => 'position_index',
            'searchPath' => 'position_search',
            'tab' => $tab,
            'numActivePosition' => $numActivePosition,
            'numPosition' => $numActivePosition,
            'numTickers' => $numTickers,
            'profit' => $profit,
            'totalDividend' => $totalDividend,
            'totalInvested' => $allocated,
        ]);
    }

    /**
     * @Route("/new/{ticker}", name="position_new", methods={"GET","POST"})
     */
    function new (Request $request, ?Ticker $ticker = null, SessionInterface $session, PositionService $positionService): Response {
        $position = new Position();

        if ($ticker instanceof Ticker) {
            $position->setTicker($ticker);
        }
        $form = $this->createForm(PositionType::class, $position);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $positionService->create($position);
            $session->set(self::SEARCH_KEY, $position->getTicker()->getTicker());
            $session->set(PortfolioController::SEARCH_KEY, $position->getTicker()->getTicker());
            return $this->redirectToRoute('portfolio_index');
        }

        return $this->render('position/new.html.twig', [
            'position' => $position,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="position_show", methods={"GET"})
     */
    public function show(Position $position): Response
    {
        return $this->render('position/show.html.twig', [
            'position' => $position,
            'netYearlyDividend' => 0.0,
        ]);
    }

    /**
     * @Route("/{id}/edit/{closed<\d+>?0}", name="position_edit", methods={"GET","POST"})
     */
    public function edit(
        Request $request,
        Position $position,
        PositionService $positionService,
        ?int $closed,
        SessionInterface $session,
        Referer $referer
    ): Response {
        if ($closed === 1) {
            $position->setClosed(true);
            $position->setClosedAt((new DateTime()));
        }

        $form = $this->createForm(PositionType::class, $position);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $positionService->update($position);
            $session->set(self::SEARCH_KEY, $position->getTicker()->getTicker());
            if ($referer->get()) {
                return $this->redirect($referer->get());
            }
            return $this->redirectToRoute('position_index');
        }

        return $this->render('position/edit.html.twig', [
            'position' => $position,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="position_delete", methods={"DELETE"})
     */
    public function delete(Request $request, Position $position): Response
    {
        if ($this->isCsrfTokenValid('delete' . $position->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($position);
            $entityManager->flush();
        }

        return $this->redirectToRoute('position_index');
    }

    /**
     * @Route("/search", name="position_search", methods={"POST"})
     */
    public function search(Request $request, SessionInterface $session): Response
    {
        $searchCriteria = $request->request->get('searchCriteria');
        $session->set(self::SEARCH_KEY, $searchCriteria);

        return $this->redirectToRoute('position_index', ['orderBy' => 'buyDate', 'sort' => 'desc']);
    }
}
