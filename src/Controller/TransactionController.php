<?php

namespace App\Controller;

use App\Entity\Position;
use App\Entity\Transaction;
use App\Form\TransactionType;
use App\Repository\BranchRepository;
use App\Repository\CurrencyRepository;
use App\Repository\PositionRepository;
use App\Repository\TickerRepository;
use App\Repository\TransactionRepository;
use App\Service\ImportCsv;
use App\Service\ImportMail;
use App\Service\Referer;
use App\Service\WeightedAverage;
use DateTime;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/dashboard/transaction")
 */
class TransactionController extends AbstractController
{
    public const SEARCH_KEY = 'transaction_searchCriteria';

    /**
     * @Route("/import/mail", name="transaction_import_mail", methods={"GET","POST"})
     */
    public function import(
        TickerRepository $tickerRepository,
        CurrencyRepository $currencyRepository,
        PositionRepository $positionRepository,
        WeightedAverage $weightedAverage,
        BranchRepository $branchRepository,
        TransactionRepository $transactionRepository,
        ImportMail $importMail
    ): void {

        $entityManager = $this->getDoctrine()->getManager();
        $importMail->import($tickerRepository,
            $currencyRepository,
            $positionRepository,
            $weightedAverage,
            $branchRepository,
            $transactionRepository, $entityManager);

        exit();
    }

    /**
     * @Route("/import/csv", name="transaction_import_csv", methods={"GET","POST"})
     */
    public function importCsv(
        TickerRepository $tickerRepository,
        CurrencyRepository $currencyRepository,
        PositionRepository $positionRepository,
        WeightedAverage $weightedAverage,
        BranchRepository $branchRepository,
        TransactionRepository $transactionRepository,
        ImportCsv $importCsv
    ): void {

        $entityManager = $this->getDoctrine()->getManager();
        $importCsv->import($tickerRepository,
            $currencyRepository,
            $positionRepository,
            $weightedAverage,
            $branchRepository,
            $transactionRepository, $entityManager);

        exit();
    }

    /**
     * @Route("/list/{page}/{tab}/{orderBy}/{sort}", name="transaction_index", methods={"GET"})
     */
    public function index(
        TransactionRepository $transactionRepository,
        SessionInterface $session,
        int $page = 1,
        string $tab = 'All',
        string $orderBy = 'transactionDate',
        string $sort = 'desc'
    ): Response {
        if (!in_array($orderBy, ['transactionDate', 'ticker'])) {
            $orderBy = 'transactionDate';
        }
        if (!in_array($sort, ['asc', 'desc', 'ASC', 'DESC'])) {
            $sort = 'desc';
        }

        $searchCriteria = $session->get(self::SEARCH_KEY, '');
        $items = $transactionRepository->getAll($page, $tab, 10, $orderBy, $sort, $searchCriteria);
        $limit = 10;
        $maxPages = ceil($items->count() / $limit);
        $thisPage = $page;
        $brokers = array_merge(['All'], Transaction::BROKERS);

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
            'brokers' => $brokers,
            'tab' => $tab,
        ]);
    }

    private function presetMetrics(Transaction $transaction)
    {
        if ($transaction->getAllocation() && empty($transaction->getPrice())) {
            $transaction->setPrice($transaction->getAllocation() / ($transaction->getAmount() / 10000000));
            $transaction->setCurrency($transaction->getAllocationCurrency());
        }
        if ($transaction->getPrice() && empty($transaction->getAllocation())) {
            $transaction->setAllocation($transaction->getPrice() * ($transaction->getAmount() / 10000000));
            $transaction->setAllocationCurrency($transaction->getCurrency());
        }
    }

    /**
     * @Route("/new/{position}/{side}", name="transaction_new", methods={"GET","POST"})
     */
    function new (
        Request $request,
        Position $position,
        int $side,
        SessionInterface $session,
        CurrencyRepository $currencyRepository,
        WeightedAverage $weightedAverage,
        Referer $referer
    ): Response {
        $transaction = new Transaction();
        $currentDate = new DateTime();
        $transaction->setTransactionDate($currentDate);
        $transaction->setSide($side);
        $transaction->setPosition($position);
        $currency = $currencyRepository->findOneBy(['symbol' => 'EUR']);
        $transaction->setAllocationCurrency($currency);
        $form = $this->createForm(TransactionType::class, $transaction);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->presetMetrics($transaction);
            $position->addTransaction($transaction);
            $weightedAverage->calc($position);

            if ($position->getAmount() === 0 || $position->getAmount() < 0.0001) {
                $position->setClosed(1);
                $position->setClosedAt((new DateTime()));
            }
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($position);
            $entityManager->flush();

            $session->set(self::SEARCH_KEY, $transaction->getPosition()->getTicker()->getTicker());
            $session->set(PortfolioController::SEARCH_KEY, $transaction->getPosition()->getTicker()->getTicker());

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

    /**
     * @Route("/{id}", name="transaction_show", methods={"GET"})
     */
    public function show(Transaction $transaction): Response
    {
        return $this->render('transaction/show.html.twig', [
            'transaction' => $transaction,
        ]);
    }

    /**
     * @Route("/{id}/edit/{closed<\d+>?0}", name="transaction_edit", methods={"GET","POST"})
     */
    public function edit(
        Request $request,
        Transaction $transaction,
        SessionInterface $session,
        WeightedAverage $weightedAverage,
        Referer $referer
    ): Response {
        $form = $this->createForm(TransactionType::class, $transaction);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->presetMetrics($transaction);
            $position = $transaction->getPosition();
            $weightedAverage->calc($position);
            if ($position->getAmount() === 0) {
                $position->setClosed(1);
                $position->setClosedAt((new DateTime()));
            }
            $this->getDoctrine()->getManager()->flush();
            $session->set(self::SEARCH_KEY, $transaction->getPosition()->getTicker()->getTicker());

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

    /**
     * @Route("/{id}", name="transaction_delete", methods={"DELETE"})
     */
    public function delete(
        Request $request,
        Transaction $transaction,
        Referer $referer
    ): Response {
        if ($this->isCsrfTokenValid('delete' . $transaction->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($transaction);
            $entityManager->flush();
        }

        if ($referer->get()) {
            return $this->redirect($referer->get());
        }
        return $this->redirectToRoute('transaction_index');
    }

    /**
     * @Route("/search", name="transaction_search", methods={"POST"})
     */
    public function search(Request $request, SessionInterface $session): Response
    {
        $searchCriteria = $request->request->get('searchCriteria');
        $session->set(self::SEARCH_KEY, $searchCriteria);

        return $this->redirectToRoute('transaction_index', ['orderBy' => 'transactionDate', 'sort' => 'desc']);
    }
}
