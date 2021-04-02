<?php

namespace App\Command;

use App\Repository\TickerRepository;
use App\Service\YahooFinanceService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class YahooDataCommand extends Command
{
    protected static $defaultName = 'app:yahoodata';
    protected static $defaultDescription = 'Get stockdata from yahoo for all open positions when posible and put them in a cache';
    protected $yahooFinanceService;
    protected $positionRepository;

    public function __construct(TickerRepository $tickerRepository, YahooFinanceService $yahooFinanceService)
    {
        parent::__construct();

        $this->tickerRepository = $tickerRepository;
        $this->yahooFinanceService = $yahooFinanceService;
    }
    
    protected function configure()
    {
        $this
            ->setDescription(self::$defaultDescription);
        /*    ->addArgument('arg1', InputArgument::OPTIONAL, 'Argument description')
            ->addOption('option1', null, InputOption::VALUE_NONE, 'Option description')
        ;*/
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        
        $io = new SymfonyStyle($input, $output);
        $tickers = $this->tickerRepository->getActive();
        foreach ($tickers as $ticker) {
            $symbol = $ticker->getSymbol();
            $data = $this->yahooFinanceService->getData($symbol);
            
            if (isset($data['chart']) && isset($data['chart']['result'][0]['meta']['regularMarketPrice'])) {
                $io->writeln('Got data for:'. $ticker->getSymbol());
                if (isset($data['chart']['error']) && $data['chart']['error'] !== null) {
                    $io->warning($data['chart']['error']);
                } else {
                    $symbolData = $data['chart']['result'][0]['meta'];
                    $io->info('Currency: '. $symbolData['currency']);
                    $io->info('Price: '.$symbolData['regularMarketPrice']);
                }
            } else {
                $io->warning('No data for '.$symbol);
            }
        }

        $io->success('Done....');

        return Command::SUCCESS;
    }
}
