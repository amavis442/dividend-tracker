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
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:dividenddate', description: 'Get dividend data')]
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

	protected function configure(): void
	{
		$this->addArgument(
			'by-symbol',
			InputArgument::OPTIONAL,
			'search by symbol'
		);
	}
	protected function execute(
		InputInterface $input,
		OutputInterface $output
	): int {
		$io = new SymfonyStyle($input, $output);
        $bySymbol = $input->getArgument('by-symbol');

        if ($bySymbol) {
            $ticker = $this->tickerRepository->findOneBy(['symbol' => $bySymbol]);
            $tickers[] = $ticker;
        } else {
		    $tickers = $this->tickerRepository->getActive();
        }

        $defaultCurrency = $this->currencyRepository->findOneBy([
			'symbol' => 'USD',
		]);
		$addedDates = 0;
		$addedForTicker = [];

		/**
		 * @var \App\Entity\Ticker $ticker
		 */
		foreach ($tickers as $ticker) {
			$data = $this->dividendDateService->getData(
				$ticker->getSymbol(),
				$ticker->getIsin()
			);
			$infoMsg = [];
			$io->title($ticker->getFullname(). ' ['. $ticker->getIsin().']');
			$service = $this->dividendDateService->getService(
				$ticker->getSymbol()
			);
			$url = $service->getUrl($ticker->getSymbol());
			$infoMsg[] = 'Service: ' . get_class($service);
			$infoMsg[] = 'Url: [' . $url . ']';

			if (!$data) {
				$infoMsg[] = 'No dividend data for ticker';
				$io->warning($infoMsg);
				continue;
			}
			$io->info($infoMsg);

			foreach ($data as $payment) {
				if (
					!$payment ||
					!isset($payment['Type']) ||
					$payment['Type'] === 'Unknown' ||
					empty($payment['PayDate']) ||
					empty($payment['ExDate'])
				) {
					$this->logger->alert(
						__FILE__ .
							'[' .
							__LINE__ .
							']:: Dividend date debug: ' .
							print_r($payment, true) .
							' ' .
							$ticker->getFullname() .
							' ' .
							$ticker->getSymbol()
					);
					continue;
				}
				try {
					$exDate = new DateTime($payment['ExDate']);
				} catch (\DateMalformedStringException $ex) {
					$this->logger->alert(
						'exDate exception: ' .
							print_r($payment, true) .
							' ' .
							$ticker->getFullname() .
							' ' .
							$ticker->getSymbol()
					);
					continue;
				}

				$dividendType = Calendar::REGULAR;
				if (stripos($payment['Type'], 'Extra') !== false) {
					$dividendType = Calendar::SUPPLEMENT;
				}
				if (stripos($payment['Type'], 'Return') !== false) {
					$dividendType = Calendar::SPECIAL;
				}
				$calendar = $this->calendarRepository->findOneBy([
					'ticker' => $ticker,
					'exDividendDate' => $exDate,
					'dividendType' => $dividendType,
				]);

				if (!$calendar) {
					$currencySymbol = $payment['Currency'];
					$currency = $this->currencyRepository->findOneBy([
						'symbol' => $currencySymbol,
					]);
					if (!$currency) {
						$currency = $ticker->getCurrency()
							? $ticker->getCurrency()
							: $defaultCurrency;
					}
					try {
						$payDate = new DateTime($payment['PayDate']);
					} catch (\DateMalformedStringException $ex) {
						$this->logger->alert(
							'payDate exception: ' .
								print_r($payment, true) .
								' ' .
								$ticker->getFullname() .
								' ' .
								$ticker->getSymbol()
						);
						continue;
					}
					try {
						$recordDate = new DateTime($payment['RecordDate']);
					} catch (\DateMalformedStringException $ex) {
						$this->logger->alert(
							'recordDate exception: ' .
								print_r($payment, true) .
								' ' .
								$ticker->getFullname() .
								' ' .
								$ticker->getSymbol()
						);
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
						->setDividendType($dividendType);

					try {
						$this->calendarRepository->save($calendar, true);
					} catch (Exception $e) {
						$this->logger->alert(
							'Saving date exception: ' .
								print_r($payment, true) .
								' ' .
								$ticker->getFullname() .
								' ' .
								$ticker->getSymbol()
						);
						$this->logger->alert(
							'Saving date exception (data): ' .
								print_r($data, true)
						);
						$this->logger->alert(
							'Saving date exception message:  ' .
								$e->getMessage()
						);

						throw $e;
					}

					$addedForTicker[] = $ticker->getSymbol();
					$addedDates++;
				}
			}
			sleep(10);
		}
		$io->success('Done.... added: ' . $addedDates);
		$io->info(implode(', ', $addedForTicker));
		$this->logger->debug(
			'Added: ' . $addedDates . '. ' . implode(', ', $addedForTicker)
		);

		return Command::SUCCESS;
	}
}
