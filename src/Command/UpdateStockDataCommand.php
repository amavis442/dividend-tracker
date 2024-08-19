<?php

namespace App\Command;

use App\Repository\TickerRepository;
use App\Service\StockPriceService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:update:stockdata',
    description: 'Get stockdata for all open positions when posible and put them in a cache',
)]
class UpdateStockDataCommand extends Command
{
    protected StockPriceService $stockPriceService;
    protected TickerRepository $tickerRepository;

    public function __construct(StockPriceService $stockPriceService, TickerRepository $tickerRepository)
    {
        parent::__construct();

        $this->stockPriceService = $stockPriceService;
        $this->tickerRepository = $tickerRepository;
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
