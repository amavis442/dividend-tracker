<?php

namespace App\Command\Trading212;

use App\Entity\Trading212PieMetaData;
use App\Repository\ApiKeyRepository;
use App\Repository\PieRepository;
use App\Repository\Trading212PieMetaDataRepository;
use App\Service\Trading212\PieService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[
	AsCommand(
		name: 'trading212:get-pies',
		description: 'Get trading212 meta data for pies'
	)
]
class GetPiesCommand extends Command
{
	public function __construct(
		protected HttpClientInterface $client,
		protected ApiKeyRepository $apiKeyRepository,
		protected Trading212PieMetaDataRepository $trading212PieMetaDataRepository,
		protected PieRepository $pieRepository,
		protected EntityManagerInterface $entityManager
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
		 * $var \App\Entity\ApiKey
		 */
		$apiKey = $this->apiKeyRepository->findByApiKeyName('Trading212');
		$pieService = new PieService($this->client);
		$pieService->setApiKey($apiKey->getApiKey());

		$io = new SymfonyStyle($input, $output);
		$data = $pieService->getPies();

		foreach ($data as $metaData) {
			$trading212PieMetaData = new Trading212PieMetaData();
			$trading212PieMetaData->setTrading212PieId($metaData['id']);

			$pie = $this->pieRepository->findOneBy([
				'trading212PieId' => $metaData['id'],
			]);
			if ($pie) {
				$trading212PieMetaData->setPieName($pie->getLabel());
				$trading212PieMetaData->setPie($pie);
			}

			$trading212PieMetaData->setPriceAvgInvestedValue(
				$metaData['result']['priceAvgInvestedValue']
			);
			$trading212PieMetaData->setPriceAvgValue(
				$metaData['result']['priceAvgValue']
			);

			$trading212PieMetaData->setGained(
				$metaData['dividendDetails']['gained'] ?: 0.0
			);
			$trading212PieMetaData->setReinvested(
				$metaData['dividendDetails']['reinvested']  ?: 0.0
			);
			$trading212PieMetaData->setInCash(
				$metaData['dividendDetails']['inCash']  ?: 0.0
			);

			$trading212PieMetaData->setRaw($metaData);

			$this->entityManager->persist($trading212PieMetaData);
		}
		$this->entityManager->flush();

		$io->success('Trading212 pie meta data succesful stored');

		return Command::SUCCESS;
	}
}
