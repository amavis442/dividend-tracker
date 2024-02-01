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

    protected function formatImportData(array|DOMNode $data): array
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

        $divType = Calendar::REGULAR;
        if (stripos($dividendType, 'Extra') !== false) {
            $divType = Calendar::SUPPLEMENT;
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
        $this->entityManager->flush($payment);

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
                        $row['original_price'] = $val;
                        break;
                    case 'currency (price / share)':
                        $row['original_price_currency'] = $val;
                        break;
                    case 'exchange rate':
                        $row['exchange_rate'] = $val;
                        break;
                    case 'result':
                        $row['profit'] = (float)$val;
                        break;
                    case 'currency (result)':
                        $row['profit_currency'] = $val;
                        break;
                    case 'total':
                        $allocation = $val;
                        $row['allocation'] = (float)$allocation;
                        $row['total'] = (float)$val;
                        break;
                    case 'currency (total)':
                        $row['allocation_currency'] = $val;
                        break;
                    case 'withholding tax':
                        $row['tax'] = (float) $val ?? null;
                        break;
                    case 'currency (withholding tax)':
                        $row['tax_currency'] = $val;
                        break;
                    case 'id':
                        $row['opdrachtid'] = $val;
                        break;
                    case 'currency conversion fee':
                        $row['fx_fee'] = (float) $val ?? null;
                        break;
                    case 'currency (currency conversion fee)':
                        $row['fx_fee_currency'] = (float) $val ?? null;
                        break;
                    case 'stamp duty reserve tax (eur)':
                        $row['stampduty'] += (float) $val ?? null;
                        break;
                    case 'stamp duty (eur)':
                        $row['stampduty'] += (float) $val ?? null;
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
                $row['price'] = round($row['original_price'] / $row['exchange_rate'], 3);
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
        ?Branch $branch,
        TransactionRepository $transactionRepository,
        TaxRepository $taxRepository,
        UploadedFile $uploadedFile,
        Security $security,
        CsvReader $reader
    ): array {
        $transactionsAdded = 0;
        $totalTransaction = 0;
        $transactionAlreadyExists = [];

        $file = $uploadedFile->getClientOriginalName();

        $rows = $reader->getRows();
        $rows = $this->formatImportData($rows);


        $defaultTax = $taxRepository->find(1);
        if (!$defaultTax) {
            $defaultTax = new Tax();
            $defaultTax->setTaxRate(15);
            $entityManager->persist($branch);
            $entityManager->flush();
        }

        if (!$branch) {
            $branch = new Branch();
            $branch->setLabel('Unassigned');
            $branch->setDescription('Unassigned');
            $entityManager->persist($branch);
            $entityManager->flush();
        }


        if (count($rows) > 0) {
            foreach ($rows as $row) {
                $ticker = $this->preImportCheckTicker($entityManager, $branch, $tickerRepository, $defaultTax, $row);
                $transaction = $transactionRepository->findOneBy(['jobid' => $row['opdrachtid']]);

                if (!$transaction) {
                    $position = $this->preImportCheckPosition($entityManager, $ticker, $currency, $positionRepository, $security, $row);

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
                        ->setExchangeRate($row['exchange_rate'])
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
                        ->setAvgprice(0.0)
                        ->setProfit(0.0);

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
                        ($position->getAmount() === 0 || $position->getAmount() <= 0.00000001)
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
