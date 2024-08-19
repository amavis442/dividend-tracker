<?php

namespace App\Command;

use App\Entity\Transaction;
use App\Repository\BranchRepository;
use App\Repository\CurrencyRepository;
use App\Repository\PositionRepository;
use App\Repository\TickerRepository;
use App\Repository\TransactionRepository;
use App\Service\WeightedAverage;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use App\Service\CsvReader;

#[AsCommand(
    name: 'validate',
    description: 'Validate and fix transactions based on supplied file',
)]
class ValidateCommand extends Command
{
    protected TickerRepository $tickerRepository;
    protected CurrencyRepository $currencyRepository;
    protected PositionRepository $positionRepository;
    protected WeightedAverage $weightedAverage;
    protected BranchRepository $branchRepository;
    protected TransactionRepository $transactionRepository;
    protected EntityManagerInterface $entityManager;
    protected CsvReader $csvReader;

    public function __construct(
        EntityManagerInterface $entityManager,
        TickerRepository $tickerRepository,
        CurrencyRepository $currencyRepository,
        PositionRepository $positionRepository,
        WeightedAverage $weightedAverage,
        BranchRepository $branchRepository,
        TransactionRepository $transactionRepository,
        CsvReader $csvReader
    ) {
        parent::__construct();

        $this->entityManager = $entityManager;
        $this->tickerRepository = $tickerRepository;
        $this->currencyRepository = $currencyRepository;
        $this->positionRepository = $positionRepository;
        $this->weightedAverage = $weightedAverage;
        $this->branchRepository = $branchRepository;
        $this->transactionRepository = $transactionRepository;
        $this->csvReader = $csvReader;
    }

    protected function configure(): void
    {
        $this
            ->addArgument('filename', InputArgument::REQUIRED, 'Argument description');
    }
    /**
     * @param array<string, array> $csvRows
     */
    protected function importData(array $csvRows, OutputInterface $output): void
    {
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
            }
            ;

            $row = [];
            $rawAmount = 0;
            $rawAllocation = 0;
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
                        $row['tax'] = (float) ((!isset($val) || $val == "") ?: 0.0);
                        break;
                    case 'currency (withholding tax)':
                        $row['tax_currency'] = $val;
                        break;
                    case 'id':
                        $row['opdrachtid'] = $val;
                        break;
                    case 'currency conversion fee':
                        $row['fx_fee'] = (float) ((!isset($val) || $val == "") ?: 0.0);
                        break;
                    case 'currency (currency conversion fee)':
                        $row['fx_fee_currency'] = (float) ((!isset($val) || $val == "") ?: 0.0);
                        break;
                    case 'stamp duty reserve tax (eur)':
                        $row['stampduty'] += (float) ((!isset($val) || $val == "") ?: 0.0);
                        break;
                    case 'stamp duty (eur)':
                        $row['stampduty'] += (float) ((!isset($val) || $val == "") ?: 0.0);
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
                continue;
            }
            ;

            if (count($row) > 0) {
                $rawAllocation -= (
                    ($row['fx_fee'] ?? 0) +
                    ($row['stampduty'] ?? 0) +
                    ($row['transaction_fee'] ?? 0) +
                    ($row['finra_fee'] ?? 0)
                );
                $row['allocation'] = $rawAllocation;
                $row['price'] = round($rawAllocation / $rawAmount, 3);

                $transaction = $this->transactionRepository->findOneBy(['jobid' => $row['opdrachtid']]);
                if ($transaction) {
                    $transaction
                        ->setPrice($row['price'])
                        ->setAllocation($row['allocation'])
                        ->setExchangeRate($row['wisselkoersen'])
                        ->setStampduty($row['stampduty'] ?? 0)
                        ->setFxFee($row['fx_fee'] ?? 0)
                        ->setOriginalPrice($row['original_price'])
                        ->setOriginalPriceCurrency($row['original_price_currency'])
                        ->setFinraFee($row['finra_fee'] ?? 0)
                        ->setTransactionFee($row['transaction_fee'] ?? 0)
                        ->setTotal($row['total'] ?? 0);

                    $this->entityManager->persist($transaction);
                    $this->entityManager->flush();
                }
            }
            $output->writeln('Processed: ...' . $rowNum);
            $rowNum++;
        }

        $positions = $this->positionRepository->getAllOpen();
        foreach ($positions as $position) {
            $output->writeln('Updating positions: ...#' .
                $position->getId() .
                ' ' .
                $position->getTicker()->getFullname());
            $this->weightedAverage->calc($position);
            $this->entityManager->persist($position);
            $this->entityManager->flush();
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $filename = $input->getArgument('filename');

        $this->csvReader = new CsvReader($filename);
        $cvsRows = $this->csvReader->getRows();

        $this->importData($cvsRows, $output);


        $io->success('Done.');

        return Command::SUCCESS;
    }
}
