<?php

namespace App\Controller;

use App\Entity\Currency;
use App\Entity\Position;
use App\Entity\Transaction;
use App\Entity\Ticker;
use App\Form\TransactionType;
use App\Repository\CurrencyRepository;
use App\Repository\TransactionRepository;
use App\Service\WeightedAverage;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use DateTime;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use App\Service\Referer;

use App\Repository\TickerRepository;
use App\Repository\PositionRepository;
use App\Repository\BranchRepository;
use ZBateson\MailMimeParser\MailMimeParser;
use ZBateson\MailMimeParser\Message;
use ZBateson\MailMimeParser\Header\HeaderConsts;
use DOMDocument;
use DOMElement;


/**
 * @Route("/dashboard/transaction")
 */
class TransactionController extends AbstractController
{
    public const SEARCH_KEY = 'transaction_searchCriteria';

    /**
     * @Route("/test", name="transaction_test", methods={"GET","POST"})
     */
    public function test(
        TickerRepository $tickerRepository, 
        CurrencyRepository $currencyRepository, 
        PositionRepository $positionRepository,
        WeightedAverage $weightedAverage,
        BranchRepository $branchRepository,
        TransactionRepository $transactionRepository
    )
    {
        ini_set('max_execution_time', 3000);

        // use an instance of MailMimeParser as a class dependency
        $mailParser = new MailMimeParser();

        $files = [];
        if ($handle = opendir(dirname(__DIR__).'/../test')) {
            echo "Directory handle: $handle\n";
            echo "Entries:\n";
        
            /* This is the correct way to loop over the directory. */
            while (false !== ($entry = readdir($handle))) {
                $files[] = $entry;    
            }
            closedir($handle);
        }
        sort($files);

        foreach ($files as $file) {
            if (is_dir($file)){ 
                continue;
            }
            $handle = fopen(dirname(__DIR__).'/../test/'.$file, 'r');
            $message = $mailParser->parse($handle); 
            $htmlContent = '<html>'.$message->getHtmlContent().'</html>';

            $currency = $currencyRepository->findOneBy(['symbol' => 'EUR']);
            $internalErrors = libxml_use_internal_errors(true);
            $entityManager = $this->getDoctrine()->getManager();

            $branch = $branchRepository->findOneBy(['label' => 'Tech']);
            $transactionsAdded = 0;
            $totalTransaction = 0;

            $DOM = new DOMDocument();
            $DOM->loadHTML($htmlContent);
            libxml_use_internal_errors($internalErrors);

            $tables = $DOM->getElementsByTagName('table');
            $tableNodes = $tables[3];
            $n = 0;
            $line = [];
            foreach ($tableNodes->childNodes as $childNode) {
                if ($childNode instanceof DOMElement){
                    if ($childNode->nodeName === 'tbody') {
                        foreach ($childNode->childNodes as $tNode)
                        {
                            if ($tNode->nodeName === 'tr') {
                                $row = [];
                                $r = 0;
                                foreach ($tNode->childNodes as $trNode) {
                                    if ($trNode->nodeName === 'td' && $trNode->nodeValue != '') {
                                        $val = $val = trim(str_replace("\n", "", $trNode->nodeValue));
                                        switch ($r)
                                        {
                                            case 0:
                                                $row['nr'] = $val;
                                                break;
                                            case 1:
                                                $row['opdrachtid'] = $val;
                                                break;    
                                            case 2: 
                                                [$ticker,$isin] = explode('/', $val);
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
                                                $row['amount'] = $val*100;
                                                break;
                                            case 5:
                                                $unitPrice = str_replace(" EUR",'',$val) * 100; 
                                                
                                                $row['price'] = $unitPrice;
                                                break;
                                            case 6:
                                                $allocation = str_replace(" EUR",'',$val) * 100;
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

                                if (count($row) > 0 ){
                                    $transactionDate = DateTime::createFromFormat('d-m-Y H:i:s', $row['handelsdag'].' '.$row['handelstijd']);
                                    $row['transactionDate'] = $transactionDate;

                                    $ticker = $tickerRepository->findOneBy(['ticker' => $row['ticker']]);
                                    if ($ticker && ($ticker->getIsin() == null || $ticker->getIsin() == '') ){
                                        $ticker->setIsin($row['isin']);
                                        $entityManager->persist($ticker);
                                        $entityManager->flush();
                                    }
                                    if (!$ticker) {
                                        $ticker = $tickerRepository->findOneBy(['isin' => $row['isin']]);
                                        if (!$ticker) {
                                            $ticker = new Ticker();
                                            $ticker->setTicker($row['ticker'])
                                            ->setFullname($row['ticker'])
                                            ->setIsin($row['isin'])
                                            ->setBranch($branch);
                                            
                                            $entityManager->persist($ticker);
                                            $entityManager->flush();
                                        }
                                    }

                                    if ($ticker) {
                                        $position = $positionRepository->findOneBy(['posid' => $row['opdrachtid']]);
                                        if (!$position) {
                                            $position = new Position();
                                            $position->setTicker($ticker)
                                                ->setAmount(0)
                                                ->setCurrency($currency)
                                                ->setPosid($row['opdrachtid'])
                                                ->setAllocationCurrency($currency)
                                            ; 
                                            $entityManager->persist($position);
                                            $entityManager->flush();
                                        }
                                    
                                        if ($position) {
                                            if (!$position->getPosid() || $position->getPosid() === ''){
                                                $position->setPosid($row['opdrachtid']);
                                            }

                                            $transaction = $transactionRepository->findOneBy(['transactionDate' => $row['transactionDate'], 'ticker' => $ticker]);       
                                            if (!$transaction) {
                                                $transaction = new Transaction();
                                                $transaction->setTicker($ticker)
                                                    ->setSide($row['direction'])
                                                    ->setPrice($row['price'])
                                                    ->setAllocation($row['allocation'])
                                                    ->setAmount($row['amount'])
                                                    ->setTransactionDate($row['transactionDate'])
                                                    ->setBroker('Trading212')
                                                    ->setAllocationCurrency($currency)
                                                    ->setCurrency($currency)
                                                    ->setPosition($position)
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
                                                dump('Transaction already exists. ID: '. $transaction->getId(), $row);
                                            }
                                        }
                                    
                                    } else {
                                        dump('Ticker not found....' . $row['ticker']);
                                    }   
                                    $totalTransaction++;
                                }
                            }
                        }
                    }
                }
                $n++;
            }
            sleep(2);
            dump('Done processing file '.$file. '.....', 'Transaction added: '. $transactionsAdded. ' of '. $totalTransaction);
        }
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
     * @Route("/new/{ticker}/{position}/{side}", name="transaction_new", methods={"GET","POST"})
     */
    public function new(
        Request $request,
        Ticker $ticker,
        Position $position,
        int $side,
        SessionInterface $session,
        CurrencyRepository $currencyRepository,
        WeightedAverage $weightedAverage,
        Referer $referer
    ): Response {
        $transaction = new Transaction();

        if ($ticker instanceof Ticker) {
            $transaction->setTicker($ticker);
        }
        $currentDate = new DateTime();
        $transaction->setTransactionDate($currentDate);
        $transaction->setSide($side);
        $transaction->setTicker($ticker);
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
