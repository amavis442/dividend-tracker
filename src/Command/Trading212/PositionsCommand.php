<?php

namespace App\Command\Trading212;

use App\Entity\Instrument;
use App\Repository\ApiKeyRepository;
use App\Repository\BranchRepository;
use App\Repository\CurrencyRepository;
use App\Repository\InstrumentRepository;
use App\Repository\TaxRepository;
use App\Service\Trading212\PositionService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[
	AsCommand(
		name: 'trading212:positions',
		description: 'Fetch positions from Trading212 and store/update instruments in the database'
	)
]
class PositionsCommand extends Command
{
	public function __construct(
		protected HttpClientInterface $client,
		protected ApiKeyRepository $apiKeyRepository,
		protected InstrumentRepository $instrumentRepository,
		protected CurrencyRepository $currencyRepository,
		protected BranchRepository $branchRepository,
		protected TaxRepository $taxRepository,
		protected EntityManagerInterface $entityManager,
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

		$io = new SymfonyStyle($input, $output);
		$io->note('Resolving default branch, tax and currency references...');

		/**
		 * @var ?\App\Entity\Branch $defaultBranch
		 */
		$defaultBranch = $this->branchRepository->findOneBy([]);
		/**
		 * @var ?\App\Entity\Tax $defaultTax
		 */
		$defaultTax = $this->taxRepository->findOneBy([]);

		if (!$defaultBranch) {
			$io->error('No Branch records found in the database. Create one first.');
			return Command::FAILURE;
		}
		if (!$defaultTax) {
			$io->error('No Tax records found in the database. Create one first.');
			return Command::FAILURE;
		}

		$service = new PositionService($this->client);
		$service->setApiKey($apiKey->getApiKey());

		$io->note('Fetching positions from Trading212...');

		$data = $service->getPositions();
		if (empty($data)) {
			$io->warning('No positions returned from Trading212 API.');
			return Command::SUCCESS;
		}

		$created = 0;
		$updated = 0;
		$skipped = 0;
		$createdAt = new \DateTimeImmutable();
		$updatedAt = new \DateTime();

		foreach ($data as $item) {
			$apiInstrument = $item['instrument'] ?? [];
			$symbol = $apiInstrument['ticker'] ?? '';

			if (empty($symbol)) {
				$skipped++;
				continue;
			}

			$instrument = $this->instrumentRepository->findOneBy(['symbol' => $symbol]);

			if (!$instrument) {
				$instrument = new Instrument();
				$instrument->setUuid(Uuid::v4());
				$instrument->setSymbol($symbol);
				$instrument->setName($apiInstrument['name'] ?? $symbol);
				$instrument->setIsin($apiInstrument['isin'] ?? '');

				if (!empty($apiInstrument['currency'])) {
					/**
					 * @var ?\App\Entity\Currency $currency
					 */
					$currency = $this->currencyRepository->findOneBy([
						'symbol' => $apiInstrument['currency']
					]);
					if ($currency) {
						$instrument->setCurrency($currency);
					}
				}

				if (!$instrument->getCurrency()) {
					/**
					 * @var ?\App\Entity\Currency $defaultCurrency
					 */
					$defaultCurrency = $this->currencyRepository->findOneBy([]);
					if ($defaultCurrency) {
						$instrument->setCurrency($defaultCurrency);
					}
				}

				$instrument->setBranch($defaultBranch);
				$instrument->setTax($defaultTax);
				$instrument->setCreateAt($createdAt);

				$this->entityManager->persist($instrument);
				$created++;
			} else {
				$instrument->setUpdatedAt($updatedAt);
				$updated++;
			}
		}

		$this->entityManager->flush();

		$io->success(sprintf(
			'Done — %d positions from API, %d instruments created, %d updated, %d skipped.',
			count($data), $created, $updated, $skipped
		));

		return Command::SUCCESS;
	}
}