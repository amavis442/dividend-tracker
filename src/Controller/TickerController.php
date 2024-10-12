<?php

namespace App\Controller;

use App\Entity\Ticker;
use App\Entity\TickerAutocomplete;
use App\Form\TickerAutocompleteType;
use App\Form\TickerType;
use App\Repository\TickerRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\Referer;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;

#[Route(path: '/{_locale<%app.supported_locales%>}/dashboard/ticker')]
class TickerController extends AbstractController
{
    public const SESSION_KEY = 'tickercontroller_session';

    #[
        Route(
            path: '/list/{page<\d+>?1}',
            name: 'ticker_index',
            methods: ['GET', 'POST']
        )
    ]
    public function index(
        Request $request,
        TickerRepository $tickerRepository,
        Referer $referer,
        #[MapQueryParameter] int $page = 1,
        #[MapQueryParameter] string $orderBy = 'symbol',
        #[MapQueryParameter] string $sort = 'asc'
    ): Response {
        $tickerAutoComplete = new TickerAutocomplete();
        $ticker = null;
        $referer->clear();

        $referer->set('calendar_index', [
            'page' => $page,
            'orderBy' => $orderBy,
            'sort' => $sort,
        ]);

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

        $queryBuilder = $tickerRepository->getAllQuery(
            $orderBy,
            $sort,
            $ticker
        );
        $adapter = new QueryAdapter($queryBuilder);

        $pager = new Pagerfanta($adapter);
        $pager->setMaxPerPage(10);
        $pager->setCurrentPage($page);

        return $this->render('ticker/index.html.twig', [
            'form' => $form,
            'pager' => $pager,
            'thisPage' => $page,
            'order' => $orderBy,
            'sort' => $sort,
        ]);
    }

    #[Route(path: '/create', name: 'ticker_new', methods: ['GET', 'POST'])]
    public function create(
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
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

    #[Route(path: '/{id}', name: 'ticker_show', methods: ['GET'])]
    public function show(Ticker $ticker): Response
    {
        return $this->render('ticker/show.html.twig', [
            'ticker' => $ticker,
        ]);
    }

    #[Route(path: '/{id}/edit', name: 'ticker_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Referer $referer,
        EntityManagerInterface $entityManager,
        Ticker $ticker
    ): Response {
        $form = $this->createForm(TickerType::class, $ticker);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
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

    #[
        Route(
            path: '/delete/{id}',
            name: 'ticker_delete',
            methods: ['POST', 'DELETE']
        )
    ]
    public function delete(
        Request $request,
        EntityManagerInterface $entityManager,
        Ticker $ticker
    ): Response {
        if (
            $this->isCsrfTokenValid(
                'delete' . $ticker->getId(),
                $request->request->get('_token')
            )
        ) {
            if ($ticker->getPositions()->isEmpty()) {
                $entityManager->remove($ticker);
                $entityManager->flush();
                return $this->redirectToRoute('ticker_index');
            }

            $this->addFlash(
                'notice',
                'Can not delete. Ticker is connected to open positions.'
            );
        }

        return $this->redirectToRoute('ticker_index');
    }
}
