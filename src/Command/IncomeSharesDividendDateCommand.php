<?php

namespace App\Command;

use App\Repository\TickerRepository;
use App\Service\DividendDate\IncomeSharesService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'import:incomeshares', description: 'Scrapes website for dividend dates')]
class IncomeSharesDividendDateCommand extends Command
{
	protected IncomeSharesService $incomeSharesImportService;
	protected TickerRepository $tickerRepository;

	public function __construct(
		IncomeSharesService $incomeSharesImportService,
		TickerRepository $tickerRepository
	) {
		parent::__construct();

		$this->incomeSharesImportService = $incomeSharesImportService;
		$this->tickerRepository = $tickerRepository;
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
			$io->warning('Please use valid ticker symbol. Used invalid ticker: "'. $symbol.'"');
			return 0;
		}

		$records = $this->incomeSharesImportService->getData($ticker->getSymbol(), $ticker->getIsin());

		$io->success(
			'Added ' . count($records) . ' records for ticker "' . $ticker->getFullname(). '"'
		);

		return 0;
	}
}
