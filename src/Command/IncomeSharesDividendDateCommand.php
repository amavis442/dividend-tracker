<?php

namespace App\Command;

use App\Repository\TickerRepository;
use App\Repository\CalendarRepository;
use App\Repository\CurrencyRepository;
use App\Entity\Calendar;
use Psr\Log\LoggerInterface;
use App\Service\DividendDate\IncomeSharesService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[
	AsCommand(
		name: 'import:incomeshares',
		description: 'Scrapes website for dividend dates'
	)
]
class IncomeSharesDividendDateCommand extends Command
{
	public function __construct(
		protected IncomeSharesService $incomeSharesImportService,
		protected TickerRepository $tickerRepository,
		protected CalendarRepository $calendarRepository,
		protected CurrencyRepository $currencyRepository,
		protected LoggerInterface $logger
	) {
		parent::__construct();
	}

	protected function configure(): void
	{
		$this->addArgument(
			'symbol',
			InputArgument::REQUIRED,
			'Ticker symbol for relation calendar'
		);
	}

	protected function execute(
		InputInterface $input,
		OutputInterface $output
	): int {
		$io = new SymfonyStyle($input, $output);
		$symbol = $input->getArgument('symbol');

		$ticker = $this->tickerRepository->findOneBy(['symbol' => $symbol]);
		if (!$ticker) {
			$io->warning(
				'Please use valid ticker symbol. Used invalid ticker: "' .
					$symbol .
					'"'
			);
			return 0;
		}
		$defaultCurrency = $this->currencyRepository->findOneBy([
			'symbol' => 'USD',
		]);
		$addedDates = 0;
		$data = $this->incomeSharesImportService->getData(
			$ticker->getSymbol(),
			$ticker->getIsin()
		);

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
				$exDate = new \DateTime($payment['ExDate']);
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
					$payDate = new \DateTime($payment['PayDate']);
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
					$recordDate = new \DateTime($payment['RecordDate']);
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
				} catch (\Exception $e) {
					$this->logger->alert(
						'Saving date exception: ' .
							print_r($payment, true) .
							' ' .
							$ticker->getFullname() .
							' ' .
							$ticker->getSymbol()
					);
					$this->logger->alert(
						'Saving date exception (data): ' . print_r($data, true)
					);
					$this->logger->alert(
						'Saving date exception message:  ' . $e->getMessage()
					);

					throw $e;
				}
				$addedDates++;
			}
		}

		$io->success(
			'Added ' .
				$addedDates .
				' records for ticker "' .
				$ticker->getFullname() .
				'"'
		);

		return 0;
	}
}
