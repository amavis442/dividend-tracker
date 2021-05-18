<?php

namespace App\Command;

use App\Service\StockPriceService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class StockDataCommand extends Command
{
    protected static $defaultName = 'app:yahoodata';
    protected static $defaultDescription = 'Get stockdata from yahoo for all open positions when posible and put them in a cache';
    protected $stockPriceService;

    public function __construct(StockPriceService $stockPriceService)
    {
        parent::__construct();

        $this->stockPriceService = $stockPriceService;
    }

    protected function configure()
    {
        $this
            ->setDescription(self::$defaultDescription)
            ->addArgument('symbol', InputArgument::REQUIRED, 'Ticker symbol')
        /*    ->addOption('option1', null, InputOption::VALUE_NONE, 'Option description')
        */;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {

        $io = new SymfonyStyle($input, $output);
        $symbol = $input->getArgument('symbol');
        $marketPriceInEuro = $this->stockPriceService->getQuote($symbol);
        $io->info('Euro: ' . $marketPriceInEuro);

        $io->success('Done....');

        return Command::SUCCESS;
    }
}
