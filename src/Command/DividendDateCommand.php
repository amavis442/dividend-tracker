<?php

namespace App\Command;

use App\Entity\Calendar;
use App\Repository\CalendarRepository;
use App\Repository\CurrencyRepository;
use App\Repository\TickerRepository;
use App\Service\DividendDateService;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name:'app:dividenddate',
    description:'Get dividend data',
)]
class DividendDateCommand extends Command
{
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
                try {
                    $exDate = new DateTime($payment['ExDate']);
                } catch (Exception $ex) {
                    $this->logger->alert('exDate exception: ' . print_r($payment, true) . ' ' . $ticker->getFullname() . ' ' . $ticker->getSymbol());
                    continue;
                }
                $calendar = $this->calendarRepository->findOneBy(['ticker' => $ticker, 'exDividendDate' => $exDate]);

                if (!$calendar) {
                    $currencySymbol = $payment['Currency'];
                    $currency = $this->currencyRepository->findOneBy(['symbol' => $currencySymbol]);
                    if (!$currency) {
                        $currency = $defaultCurrency;
                    }
                    try {
                        $payDate = new DateTime($payment['PayDate']);
                    } catch (Exception $ex) {
                        $this->logger->alert('payDate exception: ' . print_r($payment, true) . ' ' . $ticker->getFullname() . ' ' . $ticker->getSymbol());
                        continue;
                    }
                    try {
                        $recordDate = new DateTime($payment['RecordDate']);
                    } catch (Exception $ex) {
                        $this->logger->alert('recordDate exception: ' . print_r($payment, true) . ' ' . $ticker->getFullname() . ' ' . $ticker->getSymbol());
                        continue;
                    }

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
                        ->setDividendType(Calendar::REGULAR);
                    if (stripos($payment['Type'], 'Extra') !== false) {
                        $calendar->setDividendType(Calendar::SUPPLEMENT);
                    }
                    $this->entityManager->persist($calendar);
                    $this->entityManager->flush();

                    $addedForTicker[] = $ticker->getSymbol();
                    $addedDates++;
                }
            }
        }
        $io->success('Done.... added: ' . $addedDates);
        $io->info(implode(', ', $addedForTicker));
        $this->logger->debug('Added: ' . $addedDates . '. ' . implode(', ', $addedForTicker));

        return Command::SUCCESS;
    }
}
