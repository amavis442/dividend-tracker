<?php

namespace App\Controller;

use App\Entity\Position;
use App\Entity\Transaction;
use App\Form\TransactionType;
use App\Model\PortfolioModel;
use App\Repository\CurrencyRepository;
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

#[Route(path: '/dashboard/transaction')]
class TransactionController extends AbstractController
{
    public const SEARCH_KEY = 'transaction_searchCriteria';

    #[Route(path: '/list/{page?1}/{orderBy?transactionDate}/{sort?ASC}', name: 'transaction_index', methods: ['GET'])]
    public function index(
        Request $request,
        TransactionRepository $transactionRepository,
        int $page = 1,
        string $orderBy = 'transactionDate',
        string $sort = 'desc'
    ): Response {
        if (!in_array($orderBy, ['transactionDate', 'symbol'])) {
            $orderBy = 'transactionDate';
        }
        if (!in_array($sort, ['asc', 'desc', 'ASC', 'DESC'])) {
            $sort = 'desc';
        }

        $searchCriteria = $request->getSession()->get(self::SEARCH_KEY, '');
        $items = $transactionRepository->getAll($page, 10, $orderBy, $sort, $searchCriteria);
        $limit = 10;
        $maxPages = ceil($items->count() / $limit);
        $thisPage = $page;

        return $this->render('transaction/index.html.twig', [
            'transactions' => $items->getIterator(),
            'limit' => $limit,
            'maxPages' => $maxPages,
            'thisPage' => $thisPage,
            'order' => $orderBy,
            'sort' => $sort,
            'searchCriteria' => $searchCriteria ?? '',
            'routeName' => 'transaction_index',
            'searchPath' => 'transaction_search',
        ]);
    }

    private function presetMetrics(Transaction $transaction)
    {
        $transaction->calcAllocation();
        $transaction->calcPrice();
        $transaction->setOriginalPriceCurrency($transaction->getCurrencyOriginalPrice()->getSymbol());
        $transaction->setAllocationCurrency($transaction->getTotalCurrency());
        $transaction->setCurrency($transaction->getAllocationCurrency());

        if ($transaction->getSide() == Transaction::BUY) {
            $transaction->setProfit(0.0);
        }
    }


    #[Route(path: '/create/{position}', name: 'transaction_new', methods: ['GET', 'POST'])]
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
                    $profit = ($transaction->getPrice() - $avgPrice) * $transaction->getAmount();
                    $transaction->setProfit($profit);
                }
            }

            $uuid = Uuid::v4();
            $transaction->setUuid($uuid);

            $position->addTransaction($transaction);
            $weightedAverage->calc($position);

            if ($position->getAmount() == 0 || $position->getAmount() < 0.0001) {
                $position->setClosed(true);
                $position->setClosedAt((new DateTime()));
            }
            $entityManager->persist($position);
            $entityManager->flush();

            $request->getSession()->set(self::SEARCH_KEY, $transaction->getPosition()->getTicker()->getSymbol());
            $request->getSession()->set(PortfolioController::SEARCH_KEY, $transaction->getPosition()->getTicker()->getSymbol());

            PortfolioModel::clearCache();

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

    #[Route(path: '/{id}/edit/{closed<\d+>?0}', name: 'transaction_edit', methods: ['GET', 'POST'])]
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
                $position->setClosedAt((new DateTime()));
            }
            $entityManager->flush();

            $request->getSession()->set(self::SEARCH_KEY, $transaction->getPosition()->getTicker()->getSymbol());

            PortfolioModel::clearCache();

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
        if ($this->isCsrfTokenValid('delete' . $transaction->getId(), $request->request->get('_token'))) {
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
        $request->getSession()->set(self::SEARCH_KEY, $searchCriteria);

        return $this->redirectToRoute('transaction_index', ['orderBy' => 'transactionDate', 'sort' => 'desc']);
    }
}
