<?php

namespace App\Service;

use App\Entity\Branch;
use App\Entity\Currency;
use App\Entity\Transaction;
use App\Repository\BranchRepository;
use App\Repository\CurrencyRepository;
use App\Repository\PositionRepository;
use App\Repository\TickerRepository;
use App\Repository\TransactionRepository;
use App\Service\WeightedAverage;
use Box\Spout\Reader\Common\Creator\ReaderEntityFactory;
use Box\Spout\Reader\CSV\Sheet;
use DateTime;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ImportCsv extends ImportBase
{
    protected function formatImportData($data): array
    {
        return $this->importData($data);
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
                        $row['amount'] = $val * 10000000;
                        break;
                    case 'exchange rate':
                        $row['wisselkoersen'] = $val;
                        break;
                    case 'total (eur)':
                        $rawAllocation = $val;
                        $allocation = $val * 1000;
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
                $row['price'] = round(($rawAllocation / $rawAmount) * 1000);
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
