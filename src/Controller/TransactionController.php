<?php

namespace App\Controller;

use App\Entity\Currency;
use App\Entity\Position;
use App\Entity\Ticker;
use App\Entity\Transaction;
use App\Entity\Branch;
use App\Form\TransactionType;
use App\Repository\BranchRepository;
use App\Repository\CurrencyRepository;
use App\Repository\PositionRepository;
use App\Repository\TickerRepository;
use App\Repository\TransactionRepository;
use App\Service\Referer;
use App\Service\WeightedAverage;
use DateTime;
use DOMDocument;
use DOMElement;
use DOMNode;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use ZBateson\MailMimeParser\MailMimeParser;

/**
 * @Route("/dashboard/transaction")
 */
class TransactionController extends AbstractController
{
    public const SEARCH_KEY = 'transaction_searchCriteria';

    private function importData(DOMNode $tableNodes): ?array
    {
        $rows = [];
        foreach ($tableNodes->childNodes as $childNode) {
            if ($childNode instanceof DOMElement) {
                if ($childNode->nodeName === 'tbody') {
                    foreach ($childNode->childNodes as $tNode) {
                        if ($tNode->nodeName === 'tr') {
                            $row = [];
                            $r = 0;
                            foreach ($tNode->childNodes as $trNode) {
                                if ($trNode->nodeName === 'td' && $trNode->nodeValue != '') {
                                    $val = $val = trim(str_replace("\n", "", $trNode->nodeValue));
                                    switch ($r) {
                                        case 0:
                                            $row['nr'] = $val;
                                            break;
                                        case 1:
                                            $row['opdrachtid'] = $val;
                                            break;
                                        case 2:
                                            [$ticker, $isin] = explode('/', $val);
                                            $row['ticker'] = $ticker;
                                            $row['isin'] = $isin;
                                            break;
                                        case 3:
                                            $d = 1;
                                            if (strtolower($val) === 'verkopen') {
                                                $d = 2;
                                            }
                                            $row['direction'] = $d;
                                            break;
                                        case 4:
                                            $row['amount'] = $val * 10000000;
                                            break;
                                        case 5:
                                            $unitPrice = str_replace(" EUR", '', $val) * 1000;

                                            $row['price'] = $unitPrice;
                                            break;
                                        case 6:
                                            $allocation = str_replace(" EUR", '', $val) * 1000;
                                            $row['allocation'] = $allocation;
                                            break;
                                        case 7:
                                            $row['handelsdag'] = $val;
                                            break;
                                        case 8:
                                            $row['handelstijd'] = $val;
                                            break;
                                        case 9:
                                            $row['commisie'] = $val;
                                            break;
                                        case 10:
                                            $row['kosten_en_vergoedingen'] = $val;
                                            break;
                                        case 11:
                                            $row['opdrachttype'] = $val;
                                            break;
                                        case 12:
                                            $row['plaats_van_uitvoering'] = $val;
                                            break;
                                        case 13:
                                            $row['wisselkoersen'] = $val;
                                            break;
                                        case 14:
                                            $row['totale_prijs'] = $val;
                                            break;
                                        default:
                                            $row[] = $val;
                                    }
                                    $r++;
                                }
                            }
                            if (count($row) > 0) {
                                $transactionDate = DateTime::createFromFormat('d-m-Y H:i:s', $row['handelsdag'] . ' ' . $row['handelstijd']);
                                $row['transactionDate'] = $transactionDate;
                                $rows[$transactionDate->format('YmdHis')] = $row;
                            }
                        }
                    }
                }
            }
        }
        return $rows;
    }

    private function getImportFiles(): array
    {
        $files = [];
        if ($handle = opendir(dirname(__DIR__) . '/../import')) {
            echo "Directory handle: $handle\n";
            echo "Entries:\n";

            /* This is the correct way to loop over the directory. */
            while (false !== ($entry = readdir($handle))) {
                if (is_dir($entry)) {
                    continue;
                }
                $files[] = $entry;
            }
            closedir($handle);
        }
        return $files;
    }

    /**
     */
    private function preImportCheckTicker(
        $entityManager,
        Branch $branch,
        TickerRepository $tickerRepository,
        array $data
    ): Ticker {
        $ticker = $tickerRepository->findOneBy(['ticker' => $data['ticker']]);
        if ($ticker && ($ticker->getIsin() == null || $ticker->getIsin() == '')) {
            $ticker->setIsin($data['isin']);
            $entityManager->persist($ticker);
            $entityManager->flush();
        }
        if (!$ticker) {
            $ticker = $tickerRepository->findOneBy(['isin' => $data['isin']]);
            if (!$ticker) {
                $ticker = new Ticker();
                $ticker->setTicker($data['ticker'])
                    ->setFullname($data['ticker'])
                    ->setIsin($data['isin'])
                    ->setBranch($branch);

                $entityManager->persist($ticker);
                $entityManager->flush();
            }
        }

        return $ticker;
    }

    private function preImportCheckPosition(
        $entityManager,
        Ticker $ticker,
        Currency $currency,
        PositionRepository $positionRepository,
        array $data
    ): Position {
        $position = $positionRepository->findOneBy(['posid' => $data['opdrachtid']]);
        if (!$position) {
            $position = new Position();
            $position->setTicker($ticker)
                ->setAmount(0)
                ->setCurrency($currency)
                ->setPosid($data['opdrachtid'])
                ->setAllocationCurrency($currency)
            ;
            $entityManager->persist($position);
            $entityManager->flush();
        }

        if ($position) {
            if (!$position->getPosid() || $position->getPosid() === '') {
                $position->setPosid($data['opdrachtid']);
                $entityManager->persist($position);
                $entityManager->flush();
            }
        }

        return $position;
    }

    /**
     * @Route("/import", name="transaction_test", methods={"GET","POST"})
     */
    public function import(
        TickerRepository $tickerRepository,
        CurrencyRepository $currencyRepository,
        PositionRepository $positionRepository,
        WeightedAverage $weightedAverage,
        BranchRepository $branchRepository,
        TransactionRepository $transactionRepository
    ): void {
        ini_set('max_execution_time', 3000);

        $files = $this->getImportFiles();
        sort($files);
        $internalErrors = libxml_use_internal_errors(true);
        $entityManager = $this->getDoctrine()->getManager();
        $currency = $currencyRepository->findOneBy(['symbol' => 'EUR']);
        $branch = $branchRepository->findOneBy(['label' => 'Tech']);

        // use an instance of MailMimeParser as a class dependency
        $mailParser = new MailMimeParser();
        foreach ($files as $file) {
            $transactionsAdded = 0;
            $totalTransaction = 0;

            $handle = fopen(dirname(__DIR__) . '/../import/' . $file, 'r');
            $message = $mailParser->parse($handle);
            $htmlContent = '<html>' . $message->getHtmlContent() . '</html>';

            $DOM = new DOMDocument();
            $DOM->loadHTML($htmlContent);

            $tables = $DOM->getElementsByTagName('table');
            $tableNodes = $tables[3];
            $rows = $this->importData($tableNodes);
            
            if (count($rows) > 0) {
                ksort($rows);
                
                foreach ($rows as $row) {
                    $ticker = $this->preImportCheckTicker($entityManager, $branch, $tickerRepository, $row);
                    $position = $this->preImportCheckPosition($entityManager, $ticker, $currency, $positionRepository, $row);
                    $transaction = $transactionRepository->findOneBy(['transactionDate' => $row['transactionDate'], 'position' => $position]);
                                        
                    if (!$transaction) {
                        $transaction = new Transaction();
                        $transaction
                            ->setSide($row['direction'])
                            ->setPrice($row['price'])
                            ->setAllocation($row['allocation'])
                            ->setAmount($row['amount'])
                            ->setTransactionDate($row['transactionDate'])
                            ->setBroker('Trading212')
                            ->setAllocationCurrency($currency)
                            ->setCurrency($currency)
                            ->setPosition($position)
                            ->setExchangeRate($row['wisselkoersen'])
                            ->setJobid($row['opdrachtid'])
                        ;

                        $position->addTransaction($transaction);
                        $weightedAverage->calc($position);

                        if ($position->getAmount() === 0) {
                            $position->setClosed(1);
                        }

                        $entityManager->persist($position);
                        $entityManager->flush();
                        $transactionsAdded++;
                    } else {
                        dump('Transaction already exists. ID: ' . $transaction->getId());
                    }
                    unset($ticker, $position, $transaction);
                    
                    $totalTransaction++;
                }
            }
            fclose($handle);
            dump('Done processing file ' . $file . '.....', 'Transaction added: ' . $transactionsAdded . ' of ' . $totalTransaction);
        }
        libxml_use_internal_errors($internalErrors);

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
            $transaction->setPrice($transaction->getAllocation() / ($transaction->getAmount() / 100));
            $transaction->setCurrency($transaction->getAllocationCurrency());
        }
        if ($transaction->getPrice() && empty($transaction->getAllocation())) {
            $transaction->setAllocation($transaction->getPrice() * ($transaction->getAmount() / 100));
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

            if ($position->getAmount() === 0) {
                $position->setClosed(1);
            }
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($position);
            $entityManager->flush();

            $session->set(self::SEARCH_KEY, $transaction->getTicker()->getTicker());
            $session->set(PortfolioController::SEARCH_KEY, $transaction->getTicker()->getTicker());

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
            }
            $this->getDoctrine()->getManager()->flush();
            $session->set(self::SEARCH_KEY, $transaction->getTicker()->getTicker());

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
