<?php

namespace App\Controller;

use App\Entity\Portfolio;
use App\Entity\Position;
use App\Entity\Ticker;
use App\Form\PositionType;
use App\Repository\PortfolioRepository;
use App\Repository\PositionRepository;
use App\Repository\TickerRepository;
use App\Service\PositionService;
use App\Service\Referer;
use App\Traits\TickerAutocompleteTrait;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Pagerfanta;

#[Route(path: '/dashboard/position')]
class PositionController extends AbstractController
{
    use TickerAutocompleteTrait;

    public const SESSION_KEY = 'positioncontroller_session';

    #[
        Route(
            path: '/list/{page}/{tab}/{orderBy}/{sort}/{status}',
            name: 'position_index',
            methods: ['GET', 'POST']
        )
    ]
    public function index(
        Request $request,
        PortfolioRepository $portfolioRepository,
        PositionRepository $positionRepository,
        TickerRepository $tickerRepository,
        Referer $referer,
        int $page = 1,
        string $tab = 'All',
        string $orderBy = 'symbol',
        string $sort = 'asc',
        int $status = PositionRepository::CLOSED
    ): Response {
        if (!in_array($sort, ['asc', 'desc', 'ASC', 'DESC'])) {
            $sort = 'asc';
        }

        [$form, $_ticker] = $this->searchTicker(
            $request,
            $tickerRepository,
            self::SESSION_KEY,
            true
        );

        $referer->set('position_index', ['status' => $status]);

        /**
         * @var \App\Entity\User $user
         */
        $user = $this->getUser();
        $portfolio = $portfolioRepository->findOneBy([
            'user' => $user->getId(),
        ]);
        if (!$portfolio) {
            $portfolio = new Portfolio(); // do not want to throw an exception, but just use an empty entity
        }

        $queryBuilder = $positionRepository->getAllQuery();
        $adapter = new QueryAdapter($queryBuilder);
        $pager = new Pagerfanta($adapter);
        $pager->setMaxPerPage(10);
        $pager->setCurrentPage($page);

        return $this->render('position/index.html.twig', [
            'pager' => $pager,
            'autoCompleteForm' => $form,
            'thisPage' => $page,
            'order' => $orderBy,
            'sort' => $sort,
            'status' => $status,
            'routeName' => 'position_index',
            'searchPath' => 'position_search',
            'tab' => $tab,
            'portfolio' => $portfolio,
        ]);
    }

    #[
        Route(
            path: '/create/{ticker?}',
            name: 'position_new',
            methods: ['GET', 'POST']
        )
    ]
    public function create(
        Request $request,
        PositionService $positionService,
        ?Ticker $ticker = null
    ): Response {
        $position = new Position();

        if ($ticker != null) {
            $position->setTicker($ticker);
        }
        $form = $this->createForm(PositionType::class, $position);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $positionService->create($position);
            $request
                ->getSession()
                ->set(self::SESSION_KEY, $position->getTicker()->getSymbol());
            $request
                ->getSession()
                ->set(
                    PortfolioController::SESSION_KEY,
                    $position->getTicker()->getSymbol()
                );

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
            'ticker' => $position->getTicker(),
            'netYearlyDividend' => 0.0,
        ]);
    }

    #[
        Route(
            path: '/{id}/edit',
            name: 'position_edit',
            methods: ['GET', 'POST']
        )
    ]
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
            $request
                ->getSession()
                ->set(self::SESSION_KEY, $position->getTicker()->getSymbol());

            $refLink = $referer->get();
            if ($refLink != null) {
                return $this->redirect($refLink);
            }
            return $this->redirectToRoute('position_index');
        }

        return $this->render('position/edit.html.twig', [
            'position' => $position,
            'form' => $form->createView(),
        ]);
    }

    #[
        Route(
            path: '/delete/{id}',
            name: 'position_delete',
            methods: ['POST', 'DELETE']
        )
    ]
    public function delete(
        Request $request,
        EntityManagerInterface $entityManager,
        Position $position
    ): Response {
        if ($position->getId() == null) {
            throw new \RuntimeException('No position to remove');
        }
        $position_id = (int) $position->getId();
        if (
            $this->isCsrfTokenValid(
                'delete' . $position_id,
                (string) $request->request->get('_token', '')
            )
        ) {
            $entityManager->remove($position);
            $entityManager->flush();
        }

        return $this->redirectToRoute('position_index');
    }
}
