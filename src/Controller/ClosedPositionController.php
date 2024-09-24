<?php

namespace App\Controller;

use App\Entity\Position;
use App\Form\PositionType;
use App\Repository\PositionRepository;
use App\Repository\TickerRepository;
use App\Service\PositionService;
use App\Service\Referer;
use App\Traits\TickerAutocompleteTrait;
use DateTime;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Pagerfanta;

#[Route(path: '/dashboard/closed/position')]
class ClosedPositionController extends AbstractController
{
    use TickerAutocompleteTrait;

    public const SEARCH_KEY = 'closed_position_searchCriteria';

    #[
        Route(
            path: '/list/{page}/{sort}',
            name: 'closed_position_index',
            methods: ['GET', 'POST']
        )
    ]
    public function index(
        Request $request,
        PositionRepository $positionRepository,
        TickerRepository $tickerRepository,
        Referer $referer,
        int $page = 1,
        string $sort = 'desc'
    ): Response {
        if (!in_array($sort, ['asc', 'desc', 'ASC', 'DESC'])) {
            $sort = 'asc';
        }

        $referer->set('closed_position_index', [
            'status' => PositionRepository::CLOSED,
        ]);
        [$form, $ticker] = $this->searchTicker(
            $request,
            $tickerRepository,
            self::SEARCH_KEY,
            true
        );

        $queryBuilder = $positionRepository->getAllClosedQuery($sort, $ticker);
        $adapter = new QueryAdapter($queryBuilder);
        $pager = new Pagerfanta($adapter);
        $pager->setMaxPerPage(10);
        $pager->setCurrentPage($page);

        return $this->render('closed_position/index.html.twig', [
            'pager' => $pager,
            'thisPage' => $page,
            'sort' => $sort,
            'routeName' => 'closed_position_index',
            'searchPath' => 'closed_position_search',
            'autoCompleteForm' => $form,
        ]);
    }

    #[Route(path: '/{id}', name: 'closed_position_show', methods: ['GET'])]
    public function show(Position $position): Response
    {
        return $this->render('closed_position/show.html.twig', [
            'position' => $position,
            'ticker' => $position->getTicker(),
            'netYearlyDividend' => 0.0,
        ]);
    }

    #[
        Route(
            path: '/{id}/edit/{closed<\d+>?0}',
            name: 'closed_position_edit',
            methods: ['GET', 'POST']
        )
    ]
    public function edit(
        Request $request,
        Position $position,
        PositionService $positionService,
        ?int $closed,
        Referer $referer
    ): Response {
        if ($closed === 1) {
            $position->setClosed(true);
            $position->setClosedAt(new DateTime());
        }

        $form = $this->createForm(PositionType::class, $position);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $positionService->update($position);
            $request
                ->getSession()
                ->set(self::SEARCH_KEY, $position->getTicker()->getSymbol());
            if ($referer->get()) {
                return $this->redirect($referer->get());
            }
            return $this->redirectToRoute('closed_position_index');
        }

        return $this->render('closed_position/edit.html.twig', [
            'position' => $position,
            'form' => $form->createView(),
        ]);
    }

    #[Route(path: '/search', name: 'closed_position_search', methods: ['POST'])]
    public function search(Request $request): Response
    {
        $searchCriteria = $request->request->get('searchCriteria');
        $request->getSession()->set(self::SEARCH_KEY, $searchCriteria);

        return $this->redirectToRoute('closed_position_index', [
            'sort' => 'desc',
        ]);
    }
}
