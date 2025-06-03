<?php

namespace App\Command\Trading212;

use App\Entity\Trading212PieMetaData;
use App\Entity\Trading212PieInstrument;
use App\Repository\ApiKeyRepository;
use App\Repository\PieRepository;
use App\Repository\Trading212PieMetaDataRepository;
use App\Repository\TickerAlternativeSymbolRepository;
use App\Service\Trading212\PieService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[
	AsCommand(
		name: 'trading212:pies',
		description: 'Get trading212 meta data and pie data'
	)
]
class PieCommand extends Command
{
	protected PieService $pieService;

	public function __construct(
		protected HttpClientInterface $client,
		protected ApiKeyRepository $apiKeyRepository,
		protected Trading212PieMetaDataRepository $trading212PieMetaDataRepository,
		protected PieRepository $pieRepository,
		protected TickerAlternativeSymbolRepository $tickerAlternativeSymbolRepository,
		protected EntityManagerInterface $entityManager
	) {
		parent::__construct();
	}

	protected function init()
	{
		/**
		 * $var \App\Entity\ApiKey
		 */
		$apiKey = $this->apiKeyRepository->findByApiKeyName('Trading212');
		$this->pieService = new PieService($this->client);
		$this->pieService->setApiKey($apiKey->getApiKey());
	}

	protected function configure(): void
	{
	}

	protected function execute(
		InputInterface $input,
		OutputInterface $output
	): int {
		$this->init();

		$io = new SymfonyStyle($input, $output);
        $io->info('Getting pies meta data');
        $pieIDs = $this->processMetaData();

        sleep(30);

         $io->info('Getting content of pies');
        foreach ($pieIDs as $pieID) {
            $this->processPie($pieID, $io);

            sleep(30);
        }

		$io->success('Trading212 pie meta data succesful stored');

		return Command::SUCCESS;
	}

	protected function processMetaData(): array
	{
		$data = $this->pieService->getPies();

		$metaDataPieIds = [];
		foreach ($data as $metaData) {
			$trading212PieMetaData = new Trading212PieMetaData();
			$trading212PieMetaData->setTrading212PieId($metaData['id']);
			$metaDataPieIds[] = $metaData['id'];

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
				$metaData['dividendDetails']['reinvested'] ?: 0.0
			);
			$trading212PieMetaData->setInCash(
				$metaData['dividendDetails']['inCash'] ?: 0.0
			);

			$trading212PieMetaData->setRaw($metaData);

			$this->entityManager->persist($trading212PieMetaData);
		}
		$this->entityManager->flush();

		return $metaDataPieIds;
	}

	protected function processPie(int $pieID, SymfonyStyle $io)
	{
		$data = $this->pieService->getPie($pieID);
		$settings = $data['settings'];
		$trading212_pie_id = $settings['id'];
		$trading212_pie_name = $settings['name'];

		/**
		 * $var \App\Entity\Trading212PieMetaData
		 */
		$trading212PieMetaData = $this->trading212PieMetaDataRepository->findOneBy(
			['trading212PieId' => $trading212_pie_id],
			['createdAt' => 'DESC']
		);

		if ($trading212PieMetaData) {
			foreach ($data['instruments'] as $instrument) {
				$trading212PieInstrument = new Trading212PieInstrument();
				$trading212PieInstrument->setTrading212PieId(
					$trading212_pie_id
				);
				$trading212PieInstrument->setTickerName($instrument['ticker']);
				$trading212PieInstrument->setOwnedQuantity(
					$instrument['ownedQuantity']
				);
				$trading212PieInstrument->setPriceAvgInvestedValue(
					$instrument['result']['priceAvgInvestedValue']
				);
				$trading212PieInstrument->setPriceAvgValue(
					$instrument['result']['priceAvgValue']
				);
				$trading212PieInstrument->setPriceAvgResult(
					$instrument['result']['priceAvgResult']
				);
				$trading212PieInstrument->setRaw($instrument);

				$trading212PieInstrument->setTrading212PieMetaData(
					$trading212PieMetaData
				);
				/**
				 * @var \App\Entity\TickerAlternativeSymbol
				 */
				$tickerAlternativeSymbol = $this->tickerAlternativeSymbolRepository->findOneBy(
					['symbol' => $instrument['ticker']]
				);
				if ($tickerAlternativeSymbol) {
					$trading212PieInstrument->setTicker(
						$tickerAlternativeSymbol->getTicker()
					);
				}
				$this->entityManager->persist($trading212PieInstrument);
			}

			$this->entityManager->flush();

			$io->success('Pie ' . $trading212_pie_name . ' sucessfully saved');
		} else {
			$io->warning(
				'First make sure you have the pie meta data: run php bin/console trading212:get-pies'
			);
		}
	}
}
