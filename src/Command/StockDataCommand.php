<?php

namespace App\Command;

use App\Service\StockPriceService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name:'app:yahoodata',
    description:'Get stockdata from yahoo for all open positions when posible and put them in a cache',
)]
class StockDataCommand extends Command
{
    protected $stockPriceService;

    public function __construct(StockPriceService $stockPriceService)
    {
        parent::__construct();

        $this->stockPriceService = $stockPriceService;
    }

    protected function configure(): void
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
