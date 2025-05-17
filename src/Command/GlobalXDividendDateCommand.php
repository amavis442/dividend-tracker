<?php

namespace App\Command;

use App\Repository\TickerRepository;
use App\Service\DividendDate\GlobalXImportService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'globalx:import', description: 'Reads html file')]
class GlobalXDividendDateCommand extends Command
{
	protected GlobalXImportService $globalXImportService;
	protected TickerRepository $tickerRepository;

	public function __construct(
		GlobalXImportService $globalXImportService,
		TickerRepository $tickerRepository
	) {
		parent::__construct();

		$this->globalXImportService = $globalXImportService;
		$this->tickerRepository = $tickerRepository;
	}

	protected function configure(): void
	{
		$this->addArgument(
			'symbol',
			InputArgument::REQUIRED,
			'Ticker symbol for relation calendar'
		)->addArgument(
			'filename',
			InputArgument::REQUIRED,
			'full path to html filename'
		);
	}

	protected function execute(
		InputInterface $input,
		OutputInterface $output
	): int {
		$io = new SymfonyStyle($input, $output);
		$symbol = $input->getArgument('symbol');
		$filename = $input->getArgument('filename');

		$ticker = $this->tickerRepository->findOneBy(['symbol' => $symbol]);
		if (!$ticker) {
			$io->warning('Please use valid ticker symbol. Used invalid ticker: "'. $symbol.'"');
			return 0;
		}
		if (!file_exists($filename)) {
			$io->warning('File could not be found: "' . $filename. '"');
			return 0;
		}

		$records = $this->globalXImportService->process($filename, $ticker);

		$io->success(
			'Added ' . $records . ' records for ticker "' . $ticker->getFullname(). '"'
		);

		return 0;
	}
}
