<?php

namespace App\Controller;

use App\Entity\Position;
use App\Entity\TickerAutocomplete;
use App\Form\PositionType;
use App\Form\TickerAutocompleteType;
use App\Repository\PositionRepository;
use App\Repository\TickerRepository;
use App\Service\Position\PositionService;
use App\Service\Referer;
use DateTime;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Pagerfanta;

#[Route(path: '/{_locale<%app.supported_locales%>}/dashboard/closed/position')]
class ClosedPositionController extends AbstractController
{
    public const SESSION_KEY = 'closedportfolio_session';

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
        $tickerAutoComplete = new TickerAutocomplete();
        $ticker = null;

        $tickerAutoCompleteCache = $request
            ->getSession()
            ->get(self::SESSION_KEY, null);

        if ($tickerAutoCompleteCache instanceof TickerAutocomplete) {
            // We need a mapped entity else symfony will complain
            // This works, but i do not know if it is the best solution
            if (
                $tickerAutoCompleteCache->getTicker() &&
                $tickerAutoCompleteCache->getTicker()->getId()
            ) {
                $ticker = $tickerRepository->find(
                    $tickerAutoCompleteCache->getTicker()->getId()
                );
                $tickerAutoComplete->setTicker($ticker);
            }
        }

        /**
         * @var \Symfony\Component\Form\FormInterface $form
         */
        $form = $this->createForm(
            TickerAutocompleteType::class,
            $tickerAutoComplete,
            ['extra_options' => ['include_all_tickers' => true]]
        );
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $ticker = $tickerAutoComplete->getTicker();
            $request->getSession()->set(self::SESSION_KEY, $tickerAutoComplete);
        }

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
                ->set(self::SESSION_KEY, $position->getTicker()->getSymbol());
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
}
