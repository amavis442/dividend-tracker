<?php

namespace App\Service;

use App\Entity\Branch;
use App\Entity\Constants;
use App\Entity\Currency;
use App\Entity\Payment;
use App\Entity\Transaction;
use App\Repository\BranchRepository;
use App\Repository\CalendarRepository;
use App\Repository\CurrencyRepository;
use App\Repository\PaymentRepository;
use App\Repository\PositionRepository;
use App\Repository\TaxRepository;
use App\Repository\TickerRepository;
use App\Repository\TransactionRepository;
use App\Service\WeightedAverage;
use Box\Spout\Reader\Common\Creator\ReaderEntityFactory;
use Box\Spout\Reader\CSV\Sheet;
use DateTime;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
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

    private $importedDividendLines = 0;

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
    protected function importDividend(array $row)
    {
        $transactionDate = $row['transactionDate'];
        $isin = $row['isin'];
        $amount = $row['amount'];
        $dividend = $row['allocation'];
        $tax = $row['tax'];
        $taxCurrency = $row['tax_currency'];
        $dividendType = $row['action'];
        $dividendPaid = $row['original_price'];
        $dividendPaidCurrency = $row['original_price_currency'];

        $ticker = $this->tickerRepository->findOneBy(['isin' => $isin]);
        if (!$ticker) {
            return;
        }

        $calendar = $this->calendarRepository->findByDate($transactionDate, $ticker);
        $position = $ticker->getPositions()->last();
        if (!$position instanceof \App\Entity\Position) {
            throw new RuntimeException('There is no position for this dividend payment to link to. Are you sure you have the right account?');
        }

        $currency = $this->currencyRepository->findOneBy(['symbol' => 'EUR']);
        $payment = new Payment();

        $payment->setTicker($ticker)
            ->setCurrency($currency)
            ->setAmount($amount)
            ->setDividend($dividend)
            ->setCalendar($calendar)
            ->setPosition($position)
            ->setPayDate($transactionDate)
            ->setTaxWithold($tax)
            ->setTaxCurrency($taxCurrency)
            ->setDividendType($dividendType)
            ->setDividendPaid($dividendPaid)
            ->setDividendPaidCurrency($dividendPaidCurrency);

        if ($this->paymentRepository->hasPayment($transactionDate, $ticker, $dividendType)) {
            return;
        }

        $this->entityManager->persist($payment);
        $this->entityManager->flush($payment);

        $this->importedDividendLines++;
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

            if (false !== stripos($cellVal, 'deposit') || false !== stripos($cellVal, 'withdraw')) {
                continue;
            };

            $row = [];
            $rawAmount = 0;
            $rawAllocation = 0;
            $row['stampduty'] = 0.0;

            foreach ($cells as $r => $cell) {
                $header = $headers[$r];
                $val = $cell->getValue();
                $row['nr'] = $rowNum;
                switch ($header) {
                    case 'action':
                        $row['action'] = $val;
                        $d = Transaction::BUY;
                        if (false !== stripos($val, 'sell')) {
                            $d = Transaction::SELL;
                        }
                        $row['direction'] = $d;
                        break;
                    case 'time':
                        $row['time'] = $val;
                        $row['transactionDate'] = DateTime::createFromFormat('Y-m-d H:i:s', $val);
                        break;
                    case 'isin':
                        /**
                         * @see https://www.isin.org/isin-format/
                         */
                        $row['isin'] = $val;
                        $isin = $val;
                        if (!preg_match('/^([A-Z]{2})(\d{1})(\w+)/i', $isin, $matches) && !preg_match('/^([A-Z]{4})(\d{1})(\w+)/i', $isin, $matches)) {
                            throw new RuntimeException('ISIN Number not correct: ' . $isin);
                        }
                        break;
                    case 'ticker':
                        $row['ticker'] = $val;
                        break;
                    case 'name':
                        $row['name'] = $val;
                        break;
                    case 'result (eur)':
                        $row['profit'] = $val;
                        break;
                    case 'price / share':
                        $row['original_price'] = $val;
                        break;
                    case 'currency (price / share)':
                        $row['original_price_currency'] = $val;
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
                        $row['total'] = $val;
                        break;
                    case 'id':
                        $row['opdrachtid'] = $val;
                        break;
                    case 'withholding tax':
                        $row['tax'] = (float) $val ?? null;
                        break;
                    case 'currency (withholding tax)':
                        $row['tax_currency'] = $val;
                        break;
                    case 'stamp duty reserve tax (eur)':
                        $row['stampduty'] += (float) $val ?? null;
                        break;
                    case 'stamp duty (eur)':
                        $row['stampduty'] += (float) $val ?? null;
                        break;
                    case 'currency conversion fee (eur)':
                        $row['fx_fee'] = (float) $val ?? null;
                        break;
                    case 'Transaction fee (EUR)':
                        $row['transaction_fee'] = $val;
                        break;
                    case 'Finra fee (EUR)':
                        $row['finra_fee'] = $val;
                        break;
                    default:
                        $row[] = $val;
                }
                $r++;
            }

            if (false === stripos($cellVal, 'sell') && false === stripos($cellVal, 'buy')) {
                if (false !== stripos($cellVal, 'dividend')) {
                    $this->importDividend($row);
                }
                continue;
            };

            if (count($row) > 0) {
                $rawAllocation -= (($row['fx_fee'] ?? 0) + ($row['stampduty'] ?? 0) + ($row['transaction_fee'] ?? 0) + ($row['finra_fee'] ?? 0));
                $row['allocation'] = $rawAllocation;
                $row['price'] = round($rawAllocation / $rawAmount, 3);
                $rows[$row['nr']] = $row;
            }
            $rowNum++;
        }
        return $rows;
    }

    public function importFile(
        EntityManagerInterface $entityManager,
        TickerRepository $tickerRepository,
        PositionRepository $positionRepository,
        WeightedAverage $weightedAverage,
        Currency $currency,
        Branch $branch,
        TransactionRepository $transactionRepository,
        TaxRepository $taxRepository,
        UploadedFile $uploadedFile,
        ?\Box\Spout\Reader\CSV\Reader $reader = null
    ): array{
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

        $defaultTax = $taxRepository->find(1);

        if (count($rows) > 0) {
            foreach ($rows as $row) {
                $ticker = $this->preImportCheckTicker($entityManager, $branch, $tickerRepository, $defaultTax, $row);
                $transaction = $transactionRepository->findOneBy(['jobid' => $row['opdrachtid']]);

                if (!$transaction) {
                    $position = $this->preImportCheckPosition($entityManager, $ticker, $currency, $positionRepository, $row);

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
                        ->setStampduty($row['stampduty'] ?? 0)
                        ->setFxFee($row['fx_fee'] ?? 0)
                        ->setOriginalPrice($row['original_price'])
                        ->setOriginalPriceCurrency($row['original_price_currency'])
                        ->setFinraFee($row['finra_fee'] ?? 0)
                        ->setTransactionFee($row['transaction_fee'] ?? 0)
                        ->setTotal($row['total'] ?? 0)
                    ;
                    if ($row['direction'] == Transaction::SELL) {
                        $transaction->setProfit($row['profit']);
                    }
                    $position->addTransaction($transaction);
                    $weightedAverage->calc($position);

                    if ($position->getAmount() === 0 || $position->getAmount() < (2 / Constants::AMOUNT_PRECISION)) {
                        $position->setClosed(1);
                        $position->setClosedAt($row['transactionDate']);
                        $position->setAmount(0);
                    }

                    if ($position->getAmount() > -6 && $position->getAmount() < (2 / Constants::AMOUNT_PRECISION)) {
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
            'dividendsImported' => $this->importedDividendLines,
        ];
    }
}
