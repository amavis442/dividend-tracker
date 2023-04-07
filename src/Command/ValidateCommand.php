<?php

namespace App\Command;

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
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name:'validate',
    description:'Validate and fix transactions based on supplioed file',
)]
class ValidateCommand extends Command
{
    protected $tickerRepository;
    protected $currencyRepository;
    protected $positionRepository;
    protected $weightedAverage;
    protected $branchRepository;
    protected $transactionRepository;
    protected $importValidate;
    protected $entityManager;

    public function __construct(
        EntityManagerInterface $entityManager,
        TickerRepository $tickerRepository,
        CurrencyRepository $currencyRepository,
        PositionRepository $positionRepository,
        WeightedAverage $weightedAverage,
        BranchRepository $branchRepository,
        TransactionRepository $transactionRepository
    ) {
        parent::__construct();

        $this->entityManager = $entityManager;
        $this->tickerRepository = $tickerRepository;
        $this->currencyRepository = $currencyRepository;
        $this->positionRepository = $positionRepository;
        $this->weightedAverage = $weightedAverage;
        $this->branchRepository = $branchRepository;
        $this->transactionRepository = $transactionRepository;
    }

    protected function configure(): void
    {
        $this
            ->addArgument('filename', InputArgument::REQUIRED, 'Argument description')
        ;
    }

    protected function importData(Sheet $sheet, OutputInterface $output): void
    {
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
                        if (!preg_match('/^([A-Z]{2})(\d{1})(\w+)/i', $isin, $matches)) {
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
                continue;
            };

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

        $reader = ReaderEntityFactory::createCSVReader();
        $reader->setFieldDelimiter(',');

        $reader->open($filename);

        $sheets = $reader->getSheetIterator();
        $this->importData($sheets->current(), $output);
        $reader->close();

        $io->success('Done.');

        return Command::SUCCESS;
    }
}
