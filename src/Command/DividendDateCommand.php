<?php

namespace App\Command;

use App\Entity\Calendar;
use App\Repository\CalendarRepository;
use App\Repository\CurrencyRepository;
use App\Repository\TickerRepository;
use App\Service\DividendDateService;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class DividendDateCommand extends Command
{
    protected static $defaultName = 'app:dividenddate';
    protected static $defaultDescription = 'Get dividend data';
    /**
     * Get dividend info from external site
     *
     * @var DividendDateService
     */
    protected $dividendDateService;
    /**
     * getAll active tickers
     *
     * @var TickerRepository
     */
    protected $tickerRepository;
    /**
     * Dividend calendar
     *
     * @var CalendarRepository
     */
    protected $calendarRepository;
    /**
     * Currency
     *
     * @var CurrencyRepository
     */
    protected $currencyRepository;
    /**
     * Entity manager
     *
     * @var EntityManagerInterface
     */
    protected $entityManager;
    /**
     * Log the shit for debugging
     *
     * @var LoggerInterface
     */
    protected $logger;

    public function __construct(
        EntityManagerInterface $entityManager,
        TickerRepository $tickerRepository,
        CalendarRepository $calendarRepository,
        CurrencyRepository $currencyRepository,
        DividendDateService $dividendDateService,
        LoggerInterface $logger
    ) {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->tickerRepository = $tickerRepository;
        $this->dividendDateService = $dividendDateService;
        $this->calendarRepository = $calendarRepository;
        $this->currencyRepository = $currencyRepository;
        $this->logger = $logger;
    }

    protected function configure()
    {
        $this
            ->setDescription(self::$defaultDescription)
            /* ->addArgument('ticker', InputArgument::OPTIONAL, 'Symbol of stock (MSFT, APPL)')
        ->addOption('option1', null, InputOption::VALUE_NONE, 'Option description')
         */
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {

        $io = new SymfonyStyle($input, $output);
        $tickers = $this->tickerRepository->getActive();
        $defaultCurrency = $this->currencyRepository->findOneBy(['symbol' => 'USD']);
        $addedDates = 0;
        $addedForTicker = [];

        foreach ($tickers as $ticker) {
            $data = $this->dividendDateService->getData($ticker->getSymbol());

            if (!$data) {
                $io->info('No dividend data for ticker: ' . $ticker->getFullname());
                continue;
            }

            $io->info('Processing data for ticker: ' . $ticker->getFullname());

            foreach ($data as $payment) {
                if (!$payment || !isset($payment['Type']) || $payment['Type'] === 'Unknown' || empty($payment['PayDate']) || empty($payment['ExDate'])) {
                    $this->logger->alert('Dividend date debug: ' . print_r($payment, true) . ' ' . $ticker->getFullname() . ' ' . $ticker->getSymbol());
                    continue;
                }
                $exDate = new DateTime($payment['ExDate']);
                $calendar = $this->calendarRepository->findOneBy(['ticker' => $ticker, 'exDividendDate' => $exDate]);

                if (!$calendar) {
                    $currencySymbol = $payment['Currency'];
                    $currency = $this->currencyRepository->findOneBy(['symbol' => $currencySymbol]);
                    if (!$currency) {
                        $currency = $defaultCurrency;
                    }

                    $payDate = new DateTime($payment['PayDate']);
                    $recordDate = new DateTime($payment['RecordDate']);

                    $calendar = new Calendar();
                    $calendar
                        ->setTicker($ticker)
                        ->setCashAmount($payment['DividendAmount'])
                        ->setExDividendDate($exDate)
                        ->setPaymentDate($payDate)
                        ->setRecordDate($recordDate)
                        ->setCurrency($currency)
                        ->setSource(Calendar::SOURCE_SCRIPT)
                        ->setDescription($payment['Type'])
                    ;
                    $this->entityManager->persist($calendar);
                    $this->entityManager->flush();

                    $addedForTicker[] = $ticker->getSymbol();
                    $addedDates++;
                }

                /*
            "DividendAmount" => 0.56
            "Currency" => "USD"
            "ExDate" => "2021-05-19"
            "PayDate" => "2021-06-10"
            "RecordDate" => "2021-05-20"
            "DeclaredDate" => "2021-03-16"
            "PaymentFrequency" => "Quarterly"
            "Type" => "OrdinaryDividend"
             */
            }
        }
        $io->success('Done.... added: ' . $addedDates);
        $io->info(implode(', ', $addedForTicker));
        $this->logger->debug('Added: ' . $addedDates . '. ' . implode(', ', $addedForTicker));

        return Command::SUCCESS;
    }
}
