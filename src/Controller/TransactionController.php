<?php

namespace App\Controller;

use App\Entity\Position;
use App\Entity\Transaction;
use App\Form\TransactionType;
use App\Model\PortfolioModel;
use App\Repository\CurrencyRepository;
use App\Repository\TransactionRepository;
use App\Service\ExchangeRate\ExchangeRateService;
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
        if (!in_array($orderBy, ['transactionDate', 'ticker'])) {
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
        /*if ($transaction->getAllocation() && empty($transaction->getPrice())) {
            $transaction->setPrice($transaction->getOriginalPrice() / (float)$transaction->getExchangeRate());
            $transaction->setCurrency($transaction->getAllocationCurrency());
        }
        if ($transaction->getPrice() && empty($transaction->getAllocation())) {
            $transaction->setAllocation($transaction->getTotal());
            $transaction->setAllocationCurrency($transaction->getCurrency());
        }*/

        $transaction->setPrice($transaction->getOriginalPrice() / $transaction->getExchangeRate());
        $transaction->setCurrency($transaction->getAllocationCurrency());
        $transaction->setAllocation($transaction->getTotal());
        $transaction->setAllocationCurrency($transaction->getCurrency());
    }

    #[Route(path: '/create/{position}/{side?1}', name: 'transaction_new', methods: ['GET', 'POST'])]
    public function create(
        Request $request,
        EntityManagerInterface $entityManager,
        Position $position,
        int $side,
        CurrencyRepository $currencyRepository,
        WeightedAverage $weightedAverage,
        Referer $referer,
        ExchangeRateService $exchangeRateService
    ): Response {
        $transaction = new Transaction();
        $currentDate = new DateTime();
        $transaction->setTransactionDate($currentDate);
        $transaction->setSide($side);
        $transaction->setPosition($position);

        $rates = $exchangeRateService->getRates();

        $transaction->setExchangeRate($rates['USD']);

        $currency = $currencyRepository->findOneBy(['symbol' => 'EUR']);
        $transaction->setAllocationCurrency($currency);
        $form = $this->createForm(TransactionType::class, $transaction);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->presetMetrics($transaction);
            if ($transaction->getSide() === Transaction::BUY) {
                $transaction->setTotal($transaction->getAllocation() + $transaction->getTransactionFee());
            }

            if ($transaction->getSide() === Transaction::SELL) {
                $transaction->setTotal($transaction->getAllocation());
                $transaction->setAllocation($transaction->getAllocation() - $transaction->getTransactionFee());
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

    /* TODO! Need to rewrite export csv function
    #[Route(path: '/export/{position}', name: 'transaction_export', methods: ['GET'])]
    public function export(Position $position): Response
    {
        $transactions = $position->getTransactions();
        $tickerLabel = $position->getTicker()->getSymbol();
        $tickerName = $position->getTicker()->getFullname();
        $tickerIsin = $position->getTicker()->getIsin();

        $fname = 'export-' . $tickerLabel . '-orders-' . date('Ymd') . '.csv';
        $filename = '/tmp/' . $fname;
        $writer = WriterEntityFactory::createCSVWriter();
        $writer->setFieldDelimiter(';');
        $writer->setShouldAddBOM(false);
        $writer->openToFile($filename);

        $headers = [];
        $headers[] = 'Datum';
        $headers[] = 'Tijd';
        $headers[] = 'Type';
        $headers[] = 'Waarde';
        $headers[] = 'Transactievaluta';
        $headers[] = 'Brutobedrag';
        $headers[] = 'Valuta brutobedrag';
        $headers[] = 'Wisselkoers';
        $headers[] = 'Kosten';
        $headers[] = 'Belastingen';
        $headers[] = 'Aandelen';
        $headers[] = 'ISIN';
        $headers[] = 'Tickersymbool';
        $headers[] = 'Naam effect';

        $headersFromValues = WriterEntityFactory::createRowFromArray(array_values($headers));
        $writer->addRow($headersFromValues);

        $row = [];
        foreach ($transactions as $transaction) {
            $row = [];
            $costs = $transaction->getFxFee() + $transaction->getStampduty() + $transaction->getFinrafee();
            $total = $transaction->getTotal();
            $row['Datum'] = $transaction->getTransactionDate()->format('Y-m-d');
            $row['Tijd'] = $transaction->getTransactionDate()->format('H:i:s');
            $row['Type'] = $transaction->getSide() == Transaction::BUY ? 'Koop' : 'Verkoop';

            $grossValue = $transaction->getAmount() * $transaction->getOriginalPrice();
            $exchangerate = $transaction->getExchangeRate();
            $row['Waarde'] = number_format($total, 2, ',', '.');
            $row['Transactievaluta'] = 'EUR';
            $row['Brutobedrag'] = number_format($grossValue, 2, ',', '.');

            $exchangerate = $transaction->getExchangeRate();
            $currency = $transaction->getOriginalPriceCurrency();
            $row['Valuta brutobedrag'] = $currency ?? 'USD';
            $row['Wisselkoers'] = number_format($exchangerate, 8, ',', '.');
            $row['Kosten'] = number_format($costs, 2, ',', '.');
            $row['Belastingen'] = 0;
            $row['Aandelen'] = number_format($transaction->getAmount(), 8, ',', '.');
            $row['ISIN'] = $tickerIsin;
            $row['Tickersymbool'] = $tickerLabel;
            $row['Naam effect'] = $tickerName;

            $rowFromValues = WriterEntityFactory::createRowFromArray(array_values($row));
            $writer->addRow($rowFromValues);
        }
        $writer->close();

        $response = new BinaryFileResponse($filename);
        $disposition = HeaderUtils::makeDisposition(
            HeaderUtils::DISPOSITION_ATTACHMENT,
            $fname
        );
        $response->headers->set('Content-Disposition', $disposition);

        return $response;
    }
    */
}
