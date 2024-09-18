<?php

namespace App\Controller;

use App\Entity\Ticker;
use App\Form\TickerType;
use App\Model\PortfolioModel;
use App\Repository\TickerRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\Referer;
use App\Traits\TickerAutocompleteTrait;

#[Route(path: '/dashboard/ticker')]
class TickerController extends AbstractController
{
    use TickerAutocompleteTrait;

    public const SEARCH_KEY = 'ticker_searchCriteria';

    #[Route(path: '/list/{page<\d+>?1}', name: 'ticker_index', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        TickerRepository $tickerRepository,
        int $page = 1,
        string $orderBy = 'symbol',
        string $sort = 'asc'
    ): Response {
        $searchCriteria = $request->getSession()->get(self::SEARCH_KEY, '');
        [$form, $searchCriteria] = $this->searchTicker($request, self::SEARCH_KEY, true);

        $items = $tickerRepository->getAll($page, 10, $orderBy, $sort, $searchCriteria);
        $limit = 10;
        $maxPages = ceil($items->count() / $limit);
        $thisPage = $page;

        return $this->render('ticker/index.html.twig', [
            'tickers' => $items,
            'limit' => $limit,
            'maxPages' => $maxPages,
            'thisPage' => $thisPage,
            'routeName' => 'ticker_index',
            'searchPath' => 'ticker_search',
            'autoCompleteForm' => $form,
        ]);
    }

    #[Route(path: '/create', name: 'ticker_new', methods: ['GET', 'POST'])]
    public function create(Request $request, EntityManagerInterface $entityManager): Response
    {
        $ticker = new Ticker();
        $form = $this->createForm(TickerType::class, $ticker);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($ticker);
            $entityManager->flush();

            PortfolioModel::clearCache();

            return $this->redirectToRoute('ticker_index');
        }

        return $this->render('ticker/new.html.twig', [
            'ticker' => $ticker,
            'form' => $form->createView(),
        ]);
    }

    #[Route(path: '/{id}', name: 'ticker_show', methods: ['GET'])]
    public function show(Ticker $ticker): Response
    {
        return $this->render('ticker/show.html.twig', [
            'ticker' => $ticker,
        ]);
    }

    #[Route(path: '/{id}/edit', name: 'ticker_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Referer $referer, EntityManagerInterface $entityManager, Ticker $ticker): Response
    {

        $form = $this->createForm(TickerType::class, $ticker);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            PortfolioModel::clearCache();

            if ($referer->get()) {
                return $this->redirect($referer->get());
            }
            return $this->redirectToRoute('ticker_index');
        }

        return $this->render('ticker/edit.html.twig', [
            'ticker' => $ticker,
            'form' => $form->createView(),
        ]);
    }

    #[Route(path: '/delete/{id}', name: 'ticker_delete', methods: ['POST', 'DELETE'])]
    public function delete(Request $request, EntityManagerInterface $entityManager, Ticker $ticker): Response
    {
        if ($this->isCsrfTokenValid('delete' . $ticker->getId(), $request->request->get('_token'))) {
            if ($ticker->getPositions()->isEmpty()) {
                $entityManager->remove($ticker);
                $entityManager->flush();
                return $this->redirectToRoute('ticker_index');
            }

            $this->addFlash('notice', 'Can not delete. Ticker is connected to open positions.');
        }

        return $this->redirectToRoute('ticker_index');
    }

    #[Route(path: '/search', name: 'ticker_search', methods: ['POST'])]
    public function search(Request $request): Response
    {
        $searchCriteria = $request->request->get('searchCriteria');
        $request->getSession()->set(self::SEARCH_KEY, $searchCriteria);

        return $this->redirectToRoute('ticker_index');
    }
}
