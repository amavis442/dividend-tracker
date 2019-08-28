<?php

namespace App\Controller;

use App\Entity\Ticker;
use App\Form\TickerType;
use App\Repository\TickerRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * @Route("/ticker")
 */
class TickerController extends AbstractController
{
    public const SEARCH_KEY = 'ticker_searchCriteria';

    /**
     * @Route("/list/{page<\d+>?1}", name="ticker_index", methods={"GET"})
     */
    public function index(
        TickerRepository $tickerRepository, 
        SessionInterface $session, 
        int $page = 1, 
        string $orderBy = 'ticker', 
        string $sort = 'asc'
    ): Response
    {
        $searchCriteria = $session->get(self::SEARCH_KEY, '');
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
        ]);
    }

    /**
     * @Route("/new", name="ticker_new", methods={"GET","POST"})
     */
    public function new(Request $request): Response
    {
        $ticker = new Ticker();
        $form = $this->createForm(TickerType::class, $ticker);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
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
    public function edit(Request $request, Ticker $ticker): Response
    {
        $form = $this->createForm(TickerType::class, $ticker);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

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
    public function delete(Request $request, Ticker $ticker): Response
    {
        if ($this->isCsrfTokenValid('delete'.$ticker->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($ticker);
            $entityManager->flush();
        }

        return $this->redirectToRoute('ticker_index');
    }

    /**
     * @Route("/search", name="ticker_search", methods={"POST"})
     */
    public function search(Request $request, SessionInterface $session): Response
    {
        $searchCriteria = $request->request->get('searchCriteria');
        $session->set(self::SEARCH_KEY, $searchCriteria);

        return $this->redirectToRoute('ticker_index');
    }
}
