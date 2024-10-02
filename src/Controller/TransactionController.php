<?php

namespace App\Controller;

use App\Entity\Position;
use App\Entity\TickerAutocomplete;
use App\Entity\Transaction;
use App\Form\TickerAutocompleteType;
use App\Form\TransactionType;
use App\Repository\TickerRepository;
use App\Repository\TransactionRepository;
use App\Service\ExchangeRate\ExchangeRateInterface;
use App\Service\Referer;
use App\Service\WeightedAverage;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;

#[Route(path: '/{_locale<%app.supported_locales%>}/dashboard/transaction')]
class TransactionController extends AbstractController
{
    public const SESSION_KEY = 'transaction_searchCriteria';

    #[
        Route(
            path: '/list/{page?1}/{orderBy?transactionDate}/{sort?ASC}',
            name: 'transaction_index',
            methods: ['GET', 'POST']
        )
    ]
    public function index(
        Request $request,
        TransactionRepository $transactionRepository,
        TickerRepository $tickerRepository,
        #[MapQueryParameter] int $page = 1,
        #[MapQueryParameter] string $orderBy = 'transactionDate',
        #[MapQueryParameter] string $sort = 'desc'
    ): Response {
        if (!in_array($orderBy, ['transactionDate', 'symbol'])) {
            $orderBy = 'transactionDate';
        }
        if (!in_array($sort, ['asc', 'desc', 'ASC', 'DESC'])) {
            $sort = 'desc';
        }

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

        $queryBuilder = $transactionRepository->getAllQuery(
            $orderBy,
            $sort,
            $ticker
        );

        $adapter = new QueryAdapter($queryBuilder);
        $pager = new Pagerfanta($adapter);
        $pager->setMaxPerPage(10);
        $pager->setCurrentPage($page);

        return $this->render('transaction/index.html.twig', [
            'pager' => $pager,
            'form' => $form,
            'thisPage' => $page,
            'order' => $orderBy,
            'sort' => $sort,
        ]);
    }

    private function presetMetrics(Transaction $transaction)
    {
        $transaction->calcAllocation();
        $transaction->calcPrice();
        $transaction->setOriginalPriceCurrency(
            $transaction->getCurrencyOriginalPrice()->getSymbol()
        );
        $transaction->setAllocationCurrency($transaction->getTotalCurrency());
        $transaction->setCurrency($transaction->getAllocationCurrency());

        if ($transaction->getSide() == Transaction::BUY) {
            $transaction->setProfit(0.0);
        }
    }

    #[
        Route(
            path: '/create/{position}',
            name: 'transaction_new',
            methods: ['GET', 'POST']
        )
    ]
    public function create(
        Request $request,
        EntityManagerInterface $entityManager,
        Position $position,
        WeightedAverage $weightedAverage,
        Referer $referer,
        ExchangeRateInterface $euExchangeRateService
    ): Response {
        $transaction = new Transaction();
        $currentDate = new DateTime();
        $transaction->setTransactionDate($currentDate);
        $transaction->setPosition($position);

        $rates = $euExchangeRateService->getRates();

        $transaction->setExchangeRate($rates['USD']);

        $form = $this->createForm(TransactionType::class, $transaction);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->presetMetrics($transaction);
            $transaction->setJobid(Transaction::MANUAL_ENTRY);
            $transaction->setImportfile(Transaction::MANUAL_ENTRY);
            if ($transaction->getSide() === Transaction::SELL) {
                if ($transaction->getProfit() == 0) {
                    $avgPrice = $position->getPrice();
                    $profit =
                        ($transaction->getPrice() - $avgPrice) *
                        $transaction->getAmount();
                    $transaction->setProfit($profit);
                }
            }

            $uuid = Uuid::v4();
            $transaction->setUuid($uuid);

            $position->addTransaction($transaction);
            $weightedAverage->calc($position);

            if (
                $position->getAmount() == 0 ||
                $position->getAmount() < 0.0001
            ) {
                $position->setClosed(true);
                $position->setClosedAt(new DateTime());
            }
            $entityManager->persist($position);
            $entityManager->flush();

            $request
                ->getSession()
                ->set(
                    self::SESSION_KEY,
                    $transaction->getPosition()->getTicker()->getSymbol()
                );
            $request
                ->getSession()
                ->set(
                    PortfolioController::SESSION_KEY,
                    $transaction->getPosition()->getTicker()->getSymbol()
                );

            if ($referer->get()) {
                return $this->redirect($referer->get());
            }
            return $this->redirectToRoute('portfolio_index');
        }

        return $this->render('transaction/new.html.twig', [
            'transaction' => $transaction,
            'form' => $form->createView(),
        ]);
    }

    #[Route(path: '/{id}', name: 'transaction_show', methods: ['GET'])]
    public function show(Transaction $transaction): Response
    {
        return $this->render('transaction/show.html.twig', [
            'transaction' => $transaction,
        ]);
    }

    #[
        Route(
            path: '/{id}/edit/{closed<\d+>?0}',
            name: 'transaction_edit',
            methods: ['GET', 'POST']
        )
    ]
    public function edit(
        Request $request,
        EntityManagerInterface $entityManager,
        Transaction $transaction,
        WeightedAverage $weightedAverage,
        Referer $referer
    ): Response {
        if ($transaction->getUuid() == null) {
            $uuid = Uuid::v4();
            $transaction->setUuid($uuid);
        }

        $form = $this->createForm(TransactionType::class, $transaction);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->presetMetrics($transaction);
            $position = $transaction->getPosition();
            $weightedAverage->calc($position);
            if ($position->getAmount() == 0) {
                $position->setClosed(true);
                $position->setClosedAt(new DateTime());
            }
            $entityManager->flush();

            $request
                ->getSession()
                ->set(
                    self::SESSION_KEY,
                    $transaction->getPosition()->getTicker()->getSymbol()
                );

            if ($referer->get()) {
                return $this->redirect($referer->get());
            }

            return $this->redirectToRoute('transaction_index');
        }

        return $this->render('transaction/edit.html.twig', [
            'transaction' => $transaction,
            'form' => $form->createView(),
        ]);
    }

    #[Route(path: '/{id}', name: 'transaction_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        EntityManagerInterface $entityManager,
        Transaction $transaction,
        WeightedAverage $weightedAverage,
        Referer $referer
    ): Response {
        if (
            $this->isCsrfTokenValid(
                'delete' . $transaction->getId(),
                $request->request->get('_token')
            )
        ) {
            $position = $transaction->getPosition();
            $position->removeTransaction($transaction);
            $weightedAverage->calc($position);
            $entityManager->flush();
        }

        if ($referer->get()) {
            return $this->redirect($referer->get());
        }
        return $this->redirectToRoute('transaction_index');
    }

    #[Route(path: '/search', name: 'transaction_search', methods: ['POST'])]
    public function search(Request $request): Response
    {
        $searchCriteria = $request->request->get('searchCriteria');
        $request->getSession()->set(self::SESSION_KEY, $searchCriteria);

        return $this->redirectToRoute('transaction_index', [
            'orderBy' => 'transactionDate',
            'sort' => 'desc',
        ]);
    }
}
