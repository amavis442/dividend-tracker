<?php

namespace App\Command;

use App\Repository\TickerRepository;
use App\Service\StockPriceService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class UpdateStockDataCommand extends Command
{
    protected static $defaultName = 'app:update:stockdata';
    protected static $defaultDescription = 'Get stockdata for all open positions when posible and put them in a cache';
    protected $stockPriceService;
    protected $tickerRepository;

    public function __construct(StockPriceService $stockPriceService, TickerRepository $tickerRepository)
    {
        parent::__construct();

        $this->stockPriceService = $stockPriceService;
        $this->tickerRepository = $tickerRepository;
    }

    protected function configure()
    {
        $this
            ->setDescription(self::$defaultDescription)
            /*    ->addArgument('symbol', InputArgument::REQUIRED, 'Ticker symbol')
        ->addOption('option1', null, InputOption::VALUE_NONE, 'Option description')
         */;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {

        $io = new SymfonyStyle($input, $output);
        $symbols = [];
        $tickers = $this->tickerRepository->getActive();
        foreach ($tickers as $ticker) {
            $symbol = $ticker->getSymbol();
            $symbols[] = $symbol;
        }
        $this->stockPriceService->getQuotes($symbols);
        $io->success('Done....');

        return Command::SUCCESS;
    }
}
