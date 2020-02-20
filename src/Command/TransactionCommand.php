<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use App\Repository\TransactionRepository;
use App\Repository\TickerRepository;
use App\Entity\Transaction;
use Doctrine\ORM\EntityManagerInterface;

class TransactionCommand extends Command
{
    protected static $defaultName = 'app:transaction';
    protected $transactionRepository;
    protected $tickerRepository;
    protected $em;

    public function __construct(EntityManagerInterface $em, TransactionRepository $transactionRepository, TickerRepository $tickerRepository)
    {
        parent::__construct();

        $this->em = $em;
        $this->transactionRepository = $transactionRepository;
        $this->tickerRepository = $tickerRepository;
    
    }

    protected function configure()
    {
        $this
            ->setDescription('Add a short description for your command')
            ->addArgument('arg1', InputArgument::OPTIONAL, 'Ticker symbol')
            ->addOption('force', null, InputOption::VALUE_NONE, 'overwrite')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output ): int
    {
        $io = new SymfonyStyle($input, $output);
        $arg1 = $input->getArgument('arg1');

        if ($arg1) {
            $io->note(sprintf('You passed an argument: %s', $arg1));
        }

        $overwrite = false;
        if ($input->getOption('force')) {
            $overwrite = true;
        }

        $tickers = $this->tickerRepository->getActive();

        foreach ($tickers as $ticker) {
            if ($ticker) {
                $transactions = $this->transactionRepository->getByTicker($ticker);
                if (count($transactions) === 0){
                    continue;
                }
                
                $totalAllocation = 0;
                $totalAmount = 0;
                $totalProfit = 0; //per position
                foreach ($transactions as $transaction) {
                    $profit = 0;
                    $amount = $transaction->getAmount();
                    $allocation = $transaction->getAllocation();
                    $price = round($allocation / $amount, 2);
                    
                    if ($transaction->getSide() === Transaction::BUY) {
                        $totalAmount += $amount;
                        $totalAllocation += $allocation;
                    }
                    if ($transaction->getSide() === Transaction::SELL) {

                        $profit = (int)round($allocation - $amount * $avgPrice, 0);
                        $transaction->setProfit($profit);

                        $totalAmount -= $amount;
                        $totalAllocation = $totalAmount * $avgPrice;
                        if ($profit < 0) { //loss
                            $totalAllocation -= $profit;
                        }

                        $totalProfit += $profit;
                    } 

                    $avgPrice = $totalAllocation / $totalAmount;
                    if ($ticker->getTicker() === 'SPCE') {
                        dump($transaction->getTransactionDate()->format('Y-m-d H:i:s'),$allocation, $price, $avgPrice, round($totalAmount/100,2), round($totalAllocation/100,2), round($profit/100,2), '-----');    
                    } 
                    $aPrice = (int)round($avgPrice * 100,0);
                    $transaction->setAvgprice($aPrice);

                    if ($overwrite) {
                        $this->em->persist($transaction);
                        $this->em->flush();
                    }
                }
            }
            if ($ticker->getTicker() === 'SPCE') {
                dump($totalProfit);
            }
            $positions = $ticker->getPositions();
            $position = $positions[0];
            $position->setAllocation((int)round($totalAllocation)) 
                ->setAmount((int)round($totalAmount))
                ->setPrice($aPrice)
                ->setProfit((int)round($totalProfit)); 
            if ($overwrite) {
                $this->em->persist($position);
                $this->em->flush();
            }
        }    

        return 0;
    }
}
