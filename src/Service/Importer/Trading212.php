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
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Bundle\SecurityBundle\Security;


use Banpagi\Trading212\CsvReader;
use Banpagi\Trading212\CvsTransformer;
use Banpagi\Trading212\Entity\Dividend;

final class Trading212 extends AbstractImporter
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


    /**
     * Import Dividends even when position is closed.
     *
     * @param Dividend $dividend
     * @return void
     */
    protected function importDividend(Dividend $dividend): void
    {
        $ticker = $this->tickerRepository->findOneBy(['isin' => $dividend->getIsin()]);
        if (!$ticker) {
            return;
        }

        $divType = Calendar::REGULAR;
        if (stripos($dividend->getAction(), 'Extra') !== false) {
            $divType = Calendar::SUPPLEMENT;
        }
        if (stripos($dividend->getAction(), 'Return') !== false) {
            $divType = Calendar::SPECIAL;
        }

        if ($this->paymentRepository->findOneBy(['mdHash' => $dividend->getMd5key()])) {
            return;
        }

        $calendar = $this->calendarRepository->findByDate(
            $dividend->getTransactionDate(),
            $ticker,
            $divType
        );

        /**
         * @var Position $position
         */
        $position = $ticker->getPositions()->first();

        if (!$position instanceof \App\Entity\Position) {
            throw new RuntimeException(
                'There is no position for this dividend payment to link to. Are you sure you have the right account?'
            );
        }

        if ($position->getClosed()) {
            throw new RuntimeException(
                'Cannot import for this position. Selected position is already closed.'
            );
        }

        $currency = $this->currencyRepository->findOneBy(['symbol' => 'EUR']);
        $payment = new Payment();

        $payment
            ->setTicker($ticker)
            ->setUuid($dividend->getUuid())
            ->setMdHash($dividend->getMd5key())
            ->setCurrency($currency)
            ->setAmount($dividend->getAmount())
            ->setDividend($dividend->getDividend())
            ->setCalendar($calendar)
            ->setPosition($position)
            ->setPayDate($dividend->getTransactionDate())
            ->setTaxWithold($dividend->getWithHoldingTax())
            ->setTaxCurrency($dividend->getCurrencyWithHoldingTax())
            ->setDividendType($dividend->getAction())
            ->setDividendPaid($dividend->getOriginalPrice())
            ->setDividendPaidCurrency($dividend->getCurrencyOriginalPrice())
            ->setImportfile($this->filename);

        $this->entityManager->persist($payment);
        $this->entityManager->flush();

        $this->importedDividendLines++;
    }

    public function importFile(
        UploadedFile $uploadedFile
    ): array {
        $transactionsAdded = 0;
        $totalTransaction = 0;
        $transactionAlreadyExists = [];

        $filename = $uploadedFile->getClientOriginalName();
        $this->filename = $filename;
        $reader = new CsvReader($uploadedFile->getRealPath());
        $rows = $reader->getRows();
        $transformer = new CvsTransformer();
        $dataRecords = $transformer->process($filename, $rows);

        $defaultCurrency = $this->currencyRepository->findOneBy(['symbol' => 'EUR']);

        $defaultTax = $this->taxRepository->find(1);
        if (!$defaultTax) {
            $defaultTax = new Tax();
            $defaultTax->setTaxRate(15);
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

        if ($dataRecords->getDividend()->count() > 0) {
            /**
             * @var Dividend $dividend
             */
            foreach ($dataRecords->getDividend() as $dividend) {
                $this->importDividend($dividend);
            }
        }

        if ($dataRecords->getTransaction()->count() > 0) {
            /**
             * @var \Banpagi\Trading212\Entity\Transaction $item
             */
            foreach ($dataRecords->getTransaction() as $item) {
                $data['isin'] = $item->getIsin();
                $data['ticker'] = $item->getSymbol();
                $data['name'] = $item->getName();

                $ticker = $this->preImportCheckTicker(
                    $this->entityManager,
                    $defaultBranch,
                    $this->tickerRepository,
                    $defaultTax,
                    $data
                );
                $transaction = $this->transactionRepository->findOneBy([
                    'jobid' => $item->getId(),
                ]);

                if (!$transaction) {
                    $position = $this->preImportCheckPosition(
                        $this->entityManager,
                        $ticker,
                        $defaultCurrency,
                        $this->positionRepository,
                        $this->security,
                        $item->getTransactionDate()
                    );

                    $originalPriceCurrency = $this->currencyRepository->findOneBy([
                        'symbol' => $item->getCurrencyOriginalPrice(),
                    ]);
                    $totalCurrency = $this->currencyRepository->findOneBy([
                        'symbol' => $item->getCurrencyTotal(),
                    ]);

                    $transaction = new Transaction();
                    $transaction
                        ->setSide($item->getSide())
                        ->setAmount($item->getAmount())
                        ->setTransactionDate($item->getTransactionDate())
                        ->setAllocationCurrency($defaultCurrency)
                        ->setCurrency($defaultCurrency)
                        ->setPosition($position)
                        ->setExchangeRate($item->getExchangerate())
                        ->setJobid($item->getId())
                        ->setMeta((string)$item->getRowNumber())
                        ->setImportfile($filename)
                        ->setStampduty($item->getStampduty())
                        ->setFxFee($item->getConversionFee())
                        ->setOriginalPrice($item->getOriginalPrice())
                        ->setOriginalPriceCurrency(
                            $item->getCurrencyOriginalPrice()
                        )
                        ->setFinraFee($item->getFinraFee())
                        ->setTransactionFee($item->getTransactionFee())
                        ->setTotal($item->getTotal())
                        ->setTotalCurrency($totalCurrency)
                        ->setAvgprice(0.0)
                        ->setProfit(0.0)
                        ->setUuid($item->getUuid());

                    $transaction->calcAllocation();
                    $transaction->calcPrice();
                    $transaction->setCurrencyOriginalPrice(
                        $originalPriceCurrency
                    );

                    $pies = $position->getPies();
                    if (count($pies) == 1) {
                        $transaction->setPie($pies[0]);
                    }

                    $transaction->setProfit($item->getProfit());
                    $position->addTransaction($transaction);
                    $this->weightedAverage->calc($position);

                    if (
                        (float) $position->getAmount() == 0 ||
                        (float) $position->getAmount() <= 0.00000001
                    ) {
                        $position->setClosed(true);
                        $position->setClosedAt($item->getTransactionDate());
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
            'status' => 'ok',
            'msg' =>
            'File [' .
                $uploadedFile->getClientOriginalName() .
                '] imported.',
        ];
    }
}
