<?php

namespace App\Command\Trading212;

use App\Repository\ApiKeyRepository;
use App\Service\Trading212\PositionService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[
	AsCommand(
		name: 'trading212:positions',
		description: 'Get current positions from Trading212'
	)
]
class PositionsCommand extends Command
{
	public function __construct(
		protected HttpClientInterface $client,
		protected ApiKeyRepository $apiKeyRepository,
	) {
		parent::__construct();
	}

	protected function configure(): void
	{
	}

	protected function execute(
		InputInterface $input,
		OutputInterface $output
	): int {
		/**
		 * @var \App\Entity\ApiKey $apiKey
		 */
		$apiKey = $this->apiKeyRepository->findByApiKeyName('Trading212');
		if (!$apiKey) {
			$io = new SymfonyStyle($input, $output);
			$io->error('No Trading212 API key found. Create one in the admin panel first.');
			return Command::FAILURE;
		}

		$service = new PositionService($this->client);
		$service->setApiKey($apiKey->getApiKey());

		$io = new SymfonyStyle($input, $output);
		$io->note('Fetching positions from Trading212...');

		$data = $service->getPositions();

		if (empty($data)) {
			$io->warning('No positions returned from Trading212 API.');
			return Command::SUCCESS;
		}

		$io->success(sprintf('Found %d positions:', count($data)));

		foreach ($data as $position) {
			$instrument = $position['instrument'] ?? [];
			$io->writeln(sprintf(
				'  %-10s quantity: %-8s avg price: %-10s current: %-10s',
				$instrument['ticker'] ?? '?',
				$position['quantity'] ?? 0,
				$position['averagePricePaid'] ?? '?',
				$position['currentPrice'] ?? '?'
			));
		}

		return Command::SUCCESS;
	}
}