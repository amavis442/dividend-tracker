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
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/dashboard/position')]
class PositionController extends AbstractController
{
    public const SEARCH_KEY = 'position_searchCriteria';

    #[Route(path: '/list/{page}/{tab}/{orderBy}/{sort}/{status}', name: 'position_index', methods: ['GET'])]
    public function index(
        Request $request,
        Summary $summary,
        PositionRepository $positionRepository,
        Referer $referer,
        int $page = 1,
        string $tab = 'All',
        string $orderBy = 'ticker',
        string $sort = 'asc',
        int $status = PositionRepository::CLOSED
    ): Response {
        $order = '';
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

        $searchCriteria = $request->getSession()->get(self::SEARCH_KEY, '');
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

    #[Route(path: '/create/{ticker?}', name: 'position_new', methods: ['GET', 'POST'])]
    public function create(Request $request, PositionService $positionService, ?Ticker $ticker = null): Response
    {
        $position = new Position();

        if ($ticker != null) {
            $position->setTicker($ticker);
        }
        $form = $this->createForm(PositionType::class, $position);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $positionService->create($position);
            $request->getSession()->set(self::SEARCH_KEY, $position->getTicker()->getTicker());
            $request->getSession()->set(PortfolioController::SEARCH_KEY, $position->getTicker()->getTicker());
            return $this->redirectToRoute('portfolio_index');
        }

        return $this->render('position/new.html.twig', [
            'position' => $position,
            'form' => $form->createView(),
        ]);
    }

    #[Route(path: '/{id}', name: 'position_show', methods: ['GET'])]
    public function show(Position $position): Response
    {
        return $this->render('position/show.html.twig', [
            'position' => $position,
            'netYearlyDividend' => 0.0,
        ]);
    }

    #[Route(path: '/{id}/edit', name: 'position_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Position $position,
        PositionService $positionService,
        Referer $referer
    ): Response {
        $form = $this->createForm(PositionType::class, $position);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $positionService->update($position);
            $request->getSession()->set(self::SEARCH_KEY, $position->getTicker()->getTicker());
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

    #[Route(path: '/delte/{id}', name: 'position_delete', methods: ['POST', 'DELETE'])]
    public function delete(Request $request, EntityManagerInterface $entityManager, Position $position): Response
    {
        if ($this->isCsrfTokenValid('delete' . $position->getId(), $request->request->get('_token'))) {
            $entityManager->remove($position);
            $entityManager->flush();
        }

        return $this->redirectToRoute('position_index');
    }

    #[Route(path: '/search', name: 'position_search', methods: ['POST'])]
    public function search(Request $request): Response
    {
        $searchCriteria = $request->request->get('searchCriteria');
        $request->getSession()->set(self::SEARCH_KEY, $searchCriteria);

        return $this->redirectToRoute('position_index', ['orderBy' => 'buyDate', 'sort' => 'desc']);
    }
}
