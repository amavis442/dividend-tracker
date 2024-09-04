<?php

namespace App\Service;

use App\Entity\Branch;
use App\Entity\Calendar;
use App\Entity\Currency;
use App\Entity\Payment;
use App\Entity\Transaction;
use App\Entity\Tax;
use App\Repository\BranchRepository;
use App\Repository\CalendarRepository;
use App\Repository\CurrencyRepository;
use App\Repository\PaymentRepository;
use App\Repository\PositionRepository;
use App\Repository\TaxRepository;
use App\Repository\TickerRepository;
use App\Repository\TransactionRepository;
use App\Service\WeightedAverage;
use App\Service\CsvReader;
use DateTime;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Bundle\SecurityBundle\Security;
use DOMNode;
use Symfony\Component\Uid\Uuid;

class ImportCsvService extends ImportBase
{
    /**
     * @var TickerRepository
     */
    protected TickerRepository $tickerRepository;

    /**
     * Undocumented variable
     *
     * @var CurrencyRepository
     */
    protected CurrencyRepository $currencyRepository;
    /**
     * Undocumented variable
     *
     * @var PositionRepository
     */
    protected PositionRepository $positionRepository;
    /**
     * Undocumented variable
     *
     * @var WeightedAverage
     */
    protected WeightedAverage $weightedAverage;
    /**
     * Undocumented variable
     *
     * @var BranchRepository
     */
    protected BranchRepository $branchRepository;
    /**
     * Undocumented variable
     *
     * @var TransactionRepository
     */
    protected TransactionRepository $transactionRepository;
    /**
     * Undocumented variable
     *
     * @var PaymentRepository
     */
    protected PaymentRepository $paymentRepository;
    /**
     * Undocumented variable
     *
     * @var CalendarRepository
     */
    protected CalendarRepository $calendarRepository;
    /**
     * Undocumented variable
     *
     * @var EntityManagerInterface
     */
    protected EntityManagerInterface $entityManager;

    private int $importedDividendLines = 0;

    public function __construct(
        TickerRepository $tickerRepository,
        CurrencyRepository $currencyRepository,
        PositionRepository $positionRepository,
        BranchRepository $branchRepository,
        TransactionRepository $transactionRepository,
        PaymentRepository $paymentRepository,
        CalendarRepository $calendarRepository,
        EntityManager $entityManager
    ) {
        $this->tickerRepository = $tickerRepository;
        $this->currencyRepository = $currencyRepository;
        $this->positionRepository = $positionRepository;
        $this->branchRepository = $branchRepository;
        $this->transactionRepository = $transactionRepository;
        $this->paymentRepository = $paymentRepository;
        $this->calendarRepository = $calendarRepository;
        $this->entityManager = $entityManager;
    }

    protected function formatImportData(array|DOMNode $data): array
    {
        return $this->importData($data);
    }

    /**
     * Import Dividends even when position is closed.
     *
     * @param array $row
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

        $divType = Calendar::REGULAR;
        if (stripos($dividendType, 'Extra') !== false) {
            $divType = Calendar::SUPPLEMENT;
        }
        if (stripos($dividendType, 'Return') !== false) {
            $divType = Calendar::SPECIAL;
        }

        $calendar = $this->calendarRepository->findByDate($transactionDate, $ticker, $divType);
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
        $this->entityManager->flush();

        $this->importedDividendLines++;
    }

    protected function importData(array $csvRows): ?array
    {
        $rows = [];
        $rowNum = 0;
        $csvRows = array_map(function ($row) {
            $newRow = [];
            foreach ($row as $header => $value) {
                $newRow[strtolower($header)] = $value;
            }
            return $newRow;
        }, $csvRows);

        foreach ($csvRows as $csvRow) {
            $cellVal = $csvRow['action'];

            if (false !== stripos($cellVal, 'deposit') || false !== stripos($cellVal, 'withdraw') || false !== stripos($cellVal, 'interest')) {
                continue;
            };


            $row = [];
            $row['stampduty'] = 0.0;

            foreach ($csvRow as $header => $val) {
                $header = strtolower($header);
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
                        $t = substr($val, 0, 19);
                        $row['transactionDate'] = DateTime::createFromFormat("Y-m-d H:i:s", $t);
                        if (!$row['transactionDate']) {
                            dd($csvRow, $row, $val);
                        }
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
                    case 'no. of shares':
                        $rawAmount = $val;
                        $row['amount'] = $val;
                        break;
                    case 'price / share':
                        $row['original_price'] = (float) $val;
                        break;
                    case 'currency (price / share)':
                        $row['original_price_currency'] = $val;
                        break;
                    case 'exchange rate':
                        $row['exchange_rate'] = (float) $val;
                        break;
                    case 'result':
                        $row['profit'] = (float) $val;
                        break;
                    case 'currency (result)':
                        $row['profit_currency'] = $val;
                        break;
                    case 'total':
                        $allocation = $val;
                        $row['allocation'] = (float) $allocation;
                        $row['total'] = (float) $val;
                        break;
                    case 'currency (total)':
                        $row['allocation_currency'] = $val;
                        break;
                    case 'withholding tax':
                        $row['tax'] = (float) $val ?: 0.0;
                        break;
                    case 'currency (withholding tax)':
                        $row['tax_currency'] = $val;
                        break;
                    case 'id':
                        $row['opdrachtid'] = $val;
                        break;
                    case 'currency conversion fee':
                        $row['fx_fee'] = (float) $val ?: 0.0;
                        break;
                    case 'currency (currency conversion fee)':
                        $row['fx_fee_currency'] = (float) $val ?: 0.0;
                        break;
                    case 'stamp duty reserve tax (eur)':
                        $row['stampduty'] += (float) $val ?: 0.0;
                        break;
                    case 'stamp duty (eur)':
                        $row['stampduty'] += (float) $val ?: 0.0;
                        break;
                    case 'transaction fee (eur)':
                        $row['transaction_fee'] = $val;
                        break;
                    case 'finra fee (eur)':
                        $row['finra_fee'] = $val;
                        break;
                    default:
                        $row[] = $val;
                }
            }

            if (false === stripos($cellVal, 'sell') && false === stripos($cellVal, 'buy')) {
                if (false !== stripos($cellVal, 'dividend')) {
                    $this->importDividend($row);
                }
                continue;
            };

            if (count($row) > 0) {
                $orderValue = $row['total'];
                $stockValue = $orderValue - (($row['fx_fee'] ?? 0) + ($row['stampduty'] ?? 0) + ($row['transaction_fee'] ?? 0) + ($row['finra_fee'] ?? 0));

                $row['allocation'] = $stockValue;

                try {
                    if ($row['exchange_rate'] == 0 || $row['original_price'] == 0) {
                        continue; // Some conversion has happend that you get 1 share for another which is crap.
                    } else {
                        $row['price'] = round($row['original_price'] / $row['exchange_rate'], 3);
                    }
                } catch (\Exception $e) {
                    throw new RuntimeException($e->getMessage() . ':: ' . print_r($csvRow, true), 0, $e);
                }

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
        CurrencyRepository $currencyRepository,
        BranchRepository $branchRepository,
        TransactionRepository $transactionRepository,
        TaxRepository $taxRepository,
        UploadedFile $uploadedFile,
        Security $security
    ): array {
        $transactionsAdded = 0;
        $totalTransaction = 0;
        $transactionAlreadyExists = [];

        $filename = $uploadedFile->getClientOriginalName();
        $reader = new CsvReader($uploadedFile->getRealPath());
        $rows = $reader->getRows();
        $rows = $this->formatImportData($rows);

        $defaultCurrency = $currencyRepository->findOneBy(['symbol' => 'EUR']);

        $defaultTax = $taxRepository->find(1);
        if (!$defaultTax) {
            $defaultTax = new Tax();
            $defaultTax->setTaxRate(15);
            $entityManager->persist($defaultTax);
            $entityManager->flush();
        }

        $defaultBranch = $branchRepository->findOneBy(['label' => 'Unassigned']);
        if (!$defaultBranch) {
            $defaultBranch = new Branch();
            $defaultBranch->setLabel('Unassigned');
            $defaultBranch->setDescription('Unassigned');
            $entityManager->persist($defaultBranch);
            $entityManager->flush();
        }


        if (count($rows) > 0) {
            foreach ($rows as $row) {
                $ticker = $this->preImportCheckTicker($entityManager, $defaultBranch, $tickerRepository, $defaultTax, $row);
                $transaction = $transactionRepository->findOneBy(['jobid' => $row['opdrachtid']]);

                if (!$transaction) {
                    $position = $this->preImportCheckPosition($entityManager, $ticker, $defaultCurrency, $positionRepository, $security, $row);
                    $uuid = Uuid::v4();

                    $transaction = new Transaction();
                    $transaction
                        ->setSide($row['direction'])
                        ->setPrice($row['price'])
                        ->setAllocation($row['allocation'])
                        ->setAmount($row['amount'])
                        ->setTransactionDate($row['transactionDate'])
                        ->setAllocationCurrency($defaultCurrency)
                        ->setCurrency($defaultCurrency)
                        ->setPosition($position)
                        ->setExchangeRate($row['exchange_rate'])
                        ->setJobid($row['opdrachtid'])
                        ->setMeta($row['nr'])
                        ->setImportfile($filename)
                        ->setStampduty($row['stampduty'] ?? 0)
                        ->setFxFee($row['fx_fee'] ?? 0)
                        ->setOriginalPrice($row['original_price'])
                        ->setOriginalPriceCurrency($row['original_price_currency'])
                        ->setFinraFee($row['finra_fee'] ?? 0)
                        ->setTransactionFee($row['transaction_fee'] ?? 0)
                        ->setTotal($row['total'] ?? 0)
                        ->setAvgprice(0.0)
                        ->setProfit(0.0)
                        ->setUuid($uuid)
                    ;

                    $pies = $position->getPies();
                    if (count($pies) == 1) {
                        $transaction->setPie($pies[0]);
                    }

                    if ($row['direction'] == Transaction::SELL) {
                        $transaction->setProfit($row['profit']);
                    }
                    $position->addTransaction($transaction);
                    $weightedAverage->calc($position);

                    if (
                        ((float) $position->getAmount() == 0 || (float) $position->getAmount() <= 0.00000001)
                    ) {
                        $position->setClosed(true);
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
            'status' => 'ok',
            'msg' => 'File [' . $uploadedFile->getClientOriginalName() . '] imported.',
        ];
    }
}
