<?php

namespace App\Service\Importer;

use App\Entity\Branch;
use App\Entity\Calendar;
use App\Entity\Payment;
use App\Entity\Position;
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
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Bundle\SecurityBundle\Security;
use DOMNode;
use Symfony\Component\Uid\Uuid;

class ImportCsvService extends AbstractImporter implements CsvInterface
{
    private int $importedDividendLines = 0;
    private string $filename = '';

    public function __construct(
        protected TickerRepository $tickerRepository,
        protected CurrencyRepository $currencyRepository,
        protected PositionRepository $positionRepository,
        protected BranchRepository $branchRepository,
        protected TransactionRepository $transactionRepository,
        protected PaymentRepository $paymentRepository,
        protected CalendarRepository $calendarRepository,
        protected EntityManagerInterface $entityManager,
        protected WeightedAverage $weightedAverage,
        protected TaxRepository $taxRepository,
        protected Security $security
    ) {}

    protected function formatImportData(array|DOMNode $data): array
    {
        return $this->processData($data);
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

        $md5key = md5(
            (string) $row['isin'] .
                (string) $row['time'] .
                (string) number_format((float)$row['total'], 2, '.', '')
        );

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

        if ($this->paymentRepository->findOneBy(['mdHash' => $md5key])) {
            return;
        }

        $calendar = $this->calendarRepository->findByDate(
            $transactionDate,
            $ticker,
            $divType
        );

        /**
         * @var Position $position
         */
        $position = $ticker->getPositions()->first();

        if (!$position instanceof Position) {
            throw new RuntimeException(
                'There is no position for this dividend payment to link to. Are you sure you have the right account?'
            );
        }

        $currency = $this->currencyRepository->findOneBy(['symbol' => 'EUR']);
        $payment = new Payment();
        $uuid = Uuid::v4();

        $payment
            ->setTicker($ticker)
            ->setUuid($uuid)
            ->setMdHash($md5key)
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
            ->setDividendPaidCurrency($dividendPaidCurrency)
            ->setImportfile($this->filename);

        $this->entityManager->persist($payment);
        $this->entityManager->flush();

        $this->importedDividendLines++;
    }

    protected function processData(array $csvRows): ?array
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

            if (
                false !== stripos($cellVal, 'deposit') ||
                false !== stripos($cellVal, 'withdraw') ||
                false !== stripos($cellVal, 'dividend adjustment') ||
                (!(stripos($cellVal, 'dividend') !== false) && false !== stripos($cellVal, 'interest'))
            ) {
                continue;
            }

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
                        $row['transactionDate'] = DateTime::createFromFormat(
                            'Y-m-d H:i:s',
                            $t
                        );
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
                        if (
                            !preg_match(
                                '/^([A-Z]{2})(\d{1})(\w+)/i',
                                $isin,
                                $matches
                            ) &&
                            !preg_match(
                                '/^([A-Z]{4})(\d{1})(\w+)/i',
                                $isin,
                                $matches
                            )
                        ) {
                            throw new RuntimeException(
                                'ISIN Number not correct: ' . $isin. ' '. print_r($csvRow, true)
                            );
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
                        $row['total_currency'] = $val;
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

            if (
                false === stripos($cellVal, 'sell') &&
                false === stripos($cellVal, 'buy')
            ) {
                if (false !== stripos($cellVal, 'dividend')) {
                    $this->importDividend($row);
                }
                continue;
            }

            if (count($row) > 0) {
                $rows[$row['nr']] = $row;
            }
            $rowNum++;
        }
        return $rows;
    }

    public function processRows(array $rows, string $filename): array
    {
        $transactionsAdded = 0;
        $totalTransaction = 0;
        $transactionAlreadyExists = [];

        $rows = $this->formatImportData($rows);
        $defaultCurrency = $this->currencyRepository->findOneBy(['symbol' => 'EUR']);

        $defaultTax = $this->taxRepository->find(1);
        if (!$defaultTax) {
            $defaultTax = new Tax();
            $defaultTax->setTaxRate(15);
            $defaultTax->setValidFrom((new \DateTime()));
            $this->entityManager->persist($defaultTax);
            $this->entityManager->flush();
        }

        $defaultBranch = $this->branchRepository->findOneBy([
            'label' => 'Unassigned',
        ]);
        if (!$defaultBranch) {
            $defaultBranch = new Branch();
            $defaultBranch->setLabel('Unassigned');
            $defaultBranch->setDescription('Unassigned');
            $this->entityManager->persist($defaultBranch);
            $this->entityManager->flush();
        }

        if (count($rows) > 0) {
            foreach ($rows as $row) {
                $ticker = $this->preImportCheckTicker(
                    $this->entityManager,
                    $defaultBranch,
                    $this->tickerRepository,
                    $defaultTax,
                    $row
                );
                $transaction = $this->transactionRepository->findOneBy([
                    'jobid' => $row['opdrachtid'],
                ]);

                if (!$transaction) {
                    $position = $this->preImportCheckPosition(
                        $this->entityManager,
                        $ticker,
                        $defaultCurrency,
                        $this->positionRepository,
                        $this->security,
                        $row['transactionDate']
                    );
                    $uuid = Uuid::v4();

                    $originalPriceCurrency = $this->currencyRepository->findOneBy([
                        'symbol' => $row['original_price_currency'],
                    ]);
                    $totalCurrency = $this->currencyRepository->findOneBy([
                        'symbol' => $row['total_currency'],
                    ]);

                    $transaction = new Transaction();
                    $transaction
                        ->setSide($row['direction'])
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
                        ->setOriginalPriceCurrency(
                            $row['original_price_currency']
                        )
                        ->setFinraFee($row['finra_fee'] ?? 0)
                        ->setTransactionFee($row['transaction_fee'] ?? 0)
                        ->setTotal($row['total'] ?? 0)
                        ->setTotalCurrency($totalCurrency)
                        ->setAvgprice(0.0)
                        ->setProfit(0.0)
                        ->setUuid($uuid);

                    $transaction->calcAllocation();
                    $transaction->calcPrice();
                    $transaction->setCurrencyOriginalPrice(
                        $originalPriceCurrency
                    );

                    $pies = $position->getPies();
                    if (count($pies) == 1) {
                        $transaction->setPie($pies[0]);
                    }

                    if ($row['direction'] == Transaction::SELL) {
                        $transaction->setProfit($row['profit'] ?? 0.0);
                    }
                    $position->addTransaction($transaction);
                    $this->weightedAverage->calc($position);

                    if (
                        (float) $position->getAmount() == 0 ||
                        (float) $position->getAmount() <= 0.00000001
                    ) {
                        $position->setClosed(true);
                        $position->setClosedAt($row['transactionDate']);
                        $position->setAmount(0);
                    }

                    $this->entityManager->persist($position);
                    $this->entityManager->flush();
                    $transactionsAdded++;
                } else {
                    $transactionAlreadyExists[] =
                        'Transaction already exists. ID: ' .
                        $transaction->getId();
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


    public function importFile(
        UploadedFile $uploadedFile,
    ): array {
        $filename = $uploadedFile->getClientOriginalName();
        $this->filename = $filename;
        $reader = new CsvReader($uploadedFile->getRealPath());
        $rows = $reader->getRows();

        $report = $this->processRows($rows, $filename);

        return array_merge($report, [
            'status' => 'ok',
            'msg' =>
            'File [' .
                $uploadedFile->getClientOriginalName() .
                '] imported.',
        ]);
    }
}
