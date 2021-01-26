<?php

namespace App\Service;

use App\Entity\Currency;
use App\Entity\Position;
use App\Entity\Ticker;
use App\Entity\Transaction;
use App\Repository\BranchRepository;
use App\Repository\CurrencyRepository;
use App\Repository\PositionRepository;
use App\Repository\TickerRepository;
use App\Repository\TransactionRepository;
use App\Service\WeightedAverage;
use DateTime;
use Doctrine\ORM\EntityManager;
use Box\Spout\Reader\Common\Creator\ReaderEntityFactory;
use Box\Spout\Reader\CSV\Sheet;

class ImportCsv extends ImportBase
{
    protected function formatImportData($data):array
    {
        return $this->importData($data);
    }

    protected function importData(Sheet $sheet): ?array
    {
        $rows = [];
        $rowNum = 0;
        foreach ($sheet->getRowIterator() as $csvRow) {
            if ($rowNum === 0) {
                $rowNum++;
                continue;
            }
            $cells = $csvRow->getCells();
            $cell = $cells[0];
            $cellVal = $cell->getValue();
            if (false === stripos($cellVal,'sell') && false === stripos($cellVal,'buy')){
                continue;
            };

            $row = [];
            $rawAmount = 0;
            $rawAllocation = 0;

            foreach ($cells as $r => $cell) 
            {
                $val = $cell->getValue();
                $row['nr'] = $rowNum;
                switch ($r) {
                    case 0:
                        $row['action'] = $val;
                        $d = 1;
                        if (false !== stripos($val,'sell')) {
                            $d = 2;
                        }
                        $row['direction'] = $d;
                        break;
                    case 1:
                        $row['time'] = $val;
                        $row['transactionDate'] = DateTime::createFromFormat('Y-m-d H:i:s', $val);;
                        break;
                    case 2:
                        $row['isin'] = $val;
                        break;
                    case 3:
                        $row['ticker'] = $val;
                        break;
                    case 4:
                        $row['name'] = $val;
                        break;
                    case 5:
                        $rawAmount = $val;
                        $row['amount'] = $val * 10000000;
                        break;
                    case 8:
                        $row['wisselkoersen'] = $val;
                        break;
                    case 10:
                        $rawAllocation = $val;
                        $allocation = $val * 1000;
                        $row['allocation'] = $allocation;
                        break;
                    case 14:
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
                    $transaction = $transactionRepository->findOneBy(['transactionDate' => $row['transactionDate'], 'position' => $position, 'meta' => $row['nr']]);

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
                            ->setMeta($row['nr'])
                            ->setImportfile($file)
                        ;

                        $position->addTransaction($transaction);
                        $weightedAverage->calc($position);

                        if ($position->getAmount() === 0) {
                            $position->setClosed(1);
                        }

                        if ($position->getAmount() > -6 && $position->getAmount() < 0) {
                            $position->setClosed(1);
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

}
