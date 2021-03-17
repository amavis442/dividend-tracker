<?php

namespace App\Service;

use App\Entity\Branch;
use App\Entity\Currency;
use App\Entity\Payment;
use App\Entity\Transaction;
use App\Repository\BranchRepository;
use App\Repository\CalendarRepository;
use App\Repository\CurrencyRepository;
use App\Repository\PaymentRepository;
use App\Repository\PositionRepository;
use App\Repository\TickerRepository;
use App\Repository\TransactionRepository;
use App\Service\WeightedAverage;
use Box\Spout\Reader\Common\Creator\ReaderEntityFactory;
use Box\Spout\Reader\CSV\Sheet;
use DateTime;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ImportCsvService extends ImportBase
{
    /**
     * @var TickerRepository
     */
    protected $tickerRepository;

    /**
     * Undocumented variable
     *
     * @var CurrencyRepository
     */
    protected $currencyRepository;
    /**
     * Undocumented variable
     *
     * @var PositionRepository
     */
    protected $positionRepository;
    /**
     * Undocumented variable
     *
     * @var WeightedAverage
     */
    protected $weightedAverage;
    /**
     * Undocumented variable
     *
     * @var BranchRepository
     */
    protected $branchRepository;
    /**
     * Undocumented variable
     *
     * @var TransactionRepository
     */
    protected $transactionRepository;
    /**
     * Undocumented variable
     *
     * @var PaymentRepository
     */
    protected $paymentRepository;
    /**
     * Undocumented variable
     *
     * @var CalendarRepository
     */
    protected $calendarRepository;
    /**
     * Undocumented variable
     *
     * @var EntityManagerInterface
     */
    protected $entityManager;

    public function __construct(
        EntityManager $entityManager
    ) {
        $this->tickerRepository = $entityManager->getRepository('App\Entity\Ticker');
        $this->currencyRepository = $entityManager->getRepository('App\Entity\Currency');
        $this->positionRepository = $entityManager->getRepository('App\Entity\Position');
        $this->branchRepository = $entityManager->getRepository('App\Entity\Branch');
        $this->transactionRepository = $entityManager->getRepository('App\Entity\Transaction');
        $this->paymentRepository = $entityManager->getRepository('App\Entity\Payment');
        $this->calendarRepository = $entityManager->getRepository('App\Entity\Calendar');
        $this->entityManager = $entityManager;
    }

    protected function formatImportData($data): array
    {
        return $this->importData($data);
    }

    /**
     * Import Dividends even when position is closed.
     *
     * @param \Box\Spout\Common\Entity\Cell[] array $cells
     * @return void
     */
    protected function importDividend(array $cells, array $headers)
    {
        $transactionDate = DateTime::createFromFormat('Y-m-d H:i:s', $cells[1]->getValue());
        $isin = $cells[2]->getValue();
        $amount = $cells[5]->getValue();
        $dividend = $cells[9]->getValue();
        if ($headers[9] == 'result (eur)') {
            $dividend = (float)$cells[10]->getValue();
        }

        $ticker = $this->tickerRepository->findOneBy(['isin' => $isin]);
        if (!$ticker) {
            return;
        }

        
        $calendar = $this->calendarRepository->findByDate($transactionDate, $ticker);
        if (!$calendar) {
            return;
        }
 
        $position = $ticker->getPositions()->last();
        $currency = $this->currencyRepository->findOneBy(['symbol' => 'EUR']);
        $payment = new Payment();

        $payment->setTicker($ticker)
            ->setCurrency($currency)
            ->setAmount($amount)
            ->setDividend($dividend)
            ->setCalendar($calendar)
            ->setPosition($position)
            ->setPayDate($transactionDate);

        if ($this->paymentRepository->hasPayment($transactionDate, $ticker)) {
            return;
        }

        $this->entityManager->persist($payment);
        $this->entityManager->flush($payment);
    }

    protected function importData(Sheet $sheet): ?array
    {
        $rows = [];
        $headers = [];
        $rowNum = 0;
        foreach ($sheet->getRowIterator() as $csvRow) {
            $cells = $csvRow->getCells();

            if ($rowNum === 0) {
                foreach ($cells as $r => $cell) {
                    $headers[$r] = strtolower($cell->getValue());
                }
                $rowNum++;
                continue;
            }
            $cell = $cells[0];
            $cellVal = $cell->getValue();
            if (false === stripos($cellVal, 'sell') && false === stripos($cellVal, 'buy')) {
                if (false !== stripos($cellVal, 'dividend')) {
                    $this->importDividend($cells, $headers);
                }
                continue;
            };

            $row = [];
            $rawAmount = 0;
            $rawAllocation = 0;

            foreach ($cells as $r => $cell) {
                $header = $headers[$r];
                $val = $cell->getValue();
                $row['nr'] = $rowNum;
                switch ($header) {
                    case 'action':
                        $row['action'] = $val;
                        $d = 1;
                        if (false !== stripos($val, 'sell')) {
                            $d = 2;
                        }
                        $row['direction'] = $d;
                        break;
                    case 'time':
                        $row['time'] = $val;
                        $row['transactionDate'] = DateTime::createFromFormat('Y-m-d H:i:s', $val);
                        break;
                    case 'isin':
                        $row['isin'] = $val;
                        break;
                    case 'ticker':
                        $row['ticker'] = $val;
                        break;
                    case 'name':
                        $row['name'] = $val;
                        break;
                    case 'no. of shares':
                        $rawAmount = $val;
                        $row['amount'] = $val;
                        break;
                    case 'exchange rate':
                        $row['wisselkoersen'] = $val;
                        break;
                    case 'total (eur)':
                        $rawAllocation = $val;
                        $allocation = $val;
                        $row['allocation'] = $allocation;
                        break;
                    case 'id':
                        $row['opdrachtid'] = $val;
                        break;
                    default:
                        $row[] = $val;
                }
                $r++;
            }
            if (count($row) > 0) {
                $row['price'] = round($rawAllocation / $rawAmount, 3);
                $rows[$row['nr']] = $row;
            }
            $rowNum++;
        }
        return $rows;
    }

    public function import(
        TickerRepository $tickerRepository,
        CurrencyRepository $currencyRepository,
        PositionRepository $positionRepository,
        WeightedAverage $weightedAverage,
        BranchRepository $branchRepository,
        TransactionRepository $transactionRepository,
        EntityManager $entityManager
    ): void {
        ini_set('max_execution_time', 3000);

        $files = $this->getImportFiles();
        sort($files);
        $currency = $currencyRepository->findOneBy(['symbol' => 'EUR']);
        $branch = $branchRepository->findOneBy(['label' => 'Tech']);

        $reader = ReaderEntityFactory::createCSVReader();
        $reader->setFieldDelimiter(',');
        foreach ($files as $file) {
            if (false === strpos($file, '.csv')) {
                continue;
            }

            $transactionsAdded = 0;
            $totalTransaction = 0;
            $filename = realpath(dirname(__DIR__) . '/../import/' . $file);
            $reader->open($filename);

            $sheets = $reader->getSheetIterator();
            $rows = $this->formatImportData($sheets->current());
            $reader->close();

            if (count($rows) > 0) {
                foreach ($rows as $row) {
                    $ticker = $this->preImportCheckTicker($entityManager, $branch, $tickerRepository, $row);
                    $position = $this->preImportCheckPosition($entityManager, $ticker, $currency, $positionRepository, $row);
                    $transaction = $transactionRepository->findOneBy(['jobid' => $row['opdrachtid']]);

                    if (!$transaction) {
                        $transaction = new Transaction();
                        $transaction
                            ->setSide($row['direction'])
                            ->setPrice($row['price'])
                            ->setAllocation($row['allocation'])
                            ->setAmount($row['amount'])
                            ->setTransactionDate($row['transactionDate'])
                            ->setAllocationCurrency($currency)
                            ->setCurrency($currency)
                            ->setPosition($position)
                            ->setExchangeRate($row['wisselkoersen'])
                            ->setJobid($row['opdrachtid'])
                            ->setMeta($row['nr'])
                            ->setImportfile($file)
                        ;

                        $position->addTransaction($transaction);
                        $weightedAverage->calc($position);

                        if ($position->getAmount() === 0 || $position->getAmount() < 2) {
                            $position->setClosed(1);
                            $position->setClosedAt($row['transactionDate']);
                            $position->setAmount(0);
                        }

                        if ($position->getAmount() > -6 && $position->getAmount() < 2) {
                            $position->setClosed(1);
                            $position->setClosedAt($row['transactionDate']);
                            $position->setAmount(0);
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
            dump('Done processing file ' . $file . '.....', 'Transaction added: ' . $transactionsAdded . ' of ' . $totalTransaction);
        }
    }

    public function importFile(
        EntityManagerInterface $entityManager,
        TickerRepository $tickerRepository,
        PositionRepository $positionRepository,
        WeightedAverage $weightedAverage,
        Currency $currency,
        Branch $branch,
        TransactionRepository $transactionRepository,
        UploadedFile $uploadedFile,
        ?\Box\Spout\Reader\CSV\Reader $reader = null
    ): array
    {
        $transactionsAdded = 0;
        $totalTransaction = 0;
        $transactionAlreadyExists = [];

        $file = $uploadedFile->getClientOriginalName();
        $filename = $uploadedFile->getRealPath();

        if (!$reader) {
            $reader = ReaderEntityFactory::createCSVReader();
            $reader->setFieldDelimiter(',');
        }
        $reader->open($filename);

        $sheets = $reader->getSheetIterator();
        $rows = $this->formatImportData($sheets->current());
        $reader->close();

        if (count($rows) > 0) {
            foreach ($rows as $row) {
                $ticker = $this->preImportCheckTicker($entityManager, $branch, $tickerRepository, $row);
                $position = $this->preImportCheckPosition($entityManager, $ticker, $currency, $positionRepository, $row);
                $transaction = $transactionRepository->findOneBy(['jobid' => $row['opdrachtid']]);

                if (!$transaction) {
                    $transaction = new Transaction();
                    $transaction
                        ->setSide($row['direction'])
                        ->setPrice($row['price'])
                        ->setAllocation($row['allocation'])
                        ->setAmount($row['amount'])
                        ->setTransactionDate($row['transactionDate'])
                        ->setAllocationCurrency($currency)
                        ->setCurrency($currency)
                        ->setPosition($position)
                        ->setExchangeRate($row['wisselkoersen'])
                        ->setJobid($row['opdrachtid'])
                        ->setMeta($row['nr'])
                        ->setImportfile($file)
                    ;

                    $position->addTransaction($transaction);
                    $weightedAverage->calc($position);

                    if ($position->getAmount() === 0 || $position->getAmount() < 2) {
                        $position->setClosed(1);
                        $position->setClosedAt($row['transactionDate']);
                        $position->setAmount(0);
                    }

                    if ($position->getAmount() > -6 && $position->getAmount() < 2) {
                        $position->setClosed(1);
                        $position->setClosedAt($row['transactionDate']);
                        $position->setAmount(0);
                    }

                    $entityManager->persist($position);
                    $entityManager->flush();
                    $transactionsAdded++;
                } else {
                    $transactionAlreadyExists[] = 'Transaction already exists. ID: ' . $transaction->getId();
                }
                unset($ticker, $position, $transaction);

                $totalTransaction++;
            }
        }
        return [
            'totalTransaction' => $totalTransaction,
            'transactionsAdded' => $transactionsAdded,
            'transactionAlreadyExists' => $transactionAlreadyExists,
        ];
    }
}
