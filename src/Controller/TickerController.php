<?php

namespace App\Controller;

use App\Entity\Ticker;
use App\Form\TickerType;
use App\Repository\TickerRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/dashboard/ticker")
 */
class TickerController extends AbstractController
{
    public const SEARCH_KEY = 'ticker_searchCriteria';

    /**
     * @Route("/list/{page<\d+>?1}", name="ticker_index", methods={"GET"})
     */
    public function index(
        Request $request,
        TickerRepository $tickerRepository,
        int $page = 1,
        string $orderBy = 'ticker',
        string $sort = 'asc'
    ): Response {
        $searchCriteria = $request->getSession()->get(self::SEARCH_KEY, '');
        $items = $tickerRepository->getAll($page, 10, $orderBy, $sort, $searchCriteria);
        $limit = 10;
        $maxPages = ceil($items->count() / $limit);
        $thisPage = $page;

        return $this->render('ticker/index.html.twig', [
            'tickers' => $items->getIterator(),
            'limit' => $limit,
            'maxPages' => $maxPages,
            'thisPage' => $thisPage,
            'searchCriteria' => $searchCriteria ?? '',
            'routeName' => 'ticker_index',
            'searchPath' => 'ticker_search'
        ]);
    }

    /**
     * @Route("/create", name="ticker_new", methods={"GET","POST"})
     */
    public function create(Request $request, EntityManagerInterface $entityManager): Response
    {
        $ticker = new Ticker();
        $form = $this->createForm(TickerType::class, $ticker);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($ticker);
            $entityManager->flush();

            return $this->redirectToRoute('ticker_index');
        }

        return $this->render('ticker/new.html.twig', [
            'ticker' => $ticker,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="ticker_show", methods={"GET"})
     */
    public function show(Ticker $ticker): Response
    {
        return $this->render('ticker/show.html.twig', [
            'ticker' => $ticker,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="ticker_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, EntityManagerInterface $entityManager, Ticker $ticker): Response
    {
        $form = $this->createForm(TickerType::class, $ticker);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('ticker_index');
        }

        return $this->render('ticker/edit.html.twig', [
            'ticker' => $ticker,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="ticker_delete", methods={"DELETE"})
     */
    public function delete(Request $request,EntityManagerInterface $entityManager, Ticker $ticker): Response
    {
        if ($this->isCsrfTokenValid('delete' . $ticker->getId(), $request->request->get('_token'))) {
            $entityManager->remove($ticker);
            $entityManager->flush();
        }

        return $this->redirectToRoute('ticker_index');
    }

    /**
     * @Route("/search", name="ticker_search", methods={"POST"})
     */
    public function search(Request $request): Response
    {
        $searchCriteria = $request->request->get('searchCriteria');
        $request->getSession()->set(self::SEARCH_KEY, $searchCriteria);

        return $this->redirectToRoute('ticker_index');
    }
}
