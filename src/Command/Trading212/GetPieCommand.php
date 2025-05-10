<?php

namespace App\Command\Trading212;

use App\Entity\PieSelect;
use App\Entity\Trading212PieInstrument;
use App\Repository\ApiKeyRepository;
use App\Repository\TickerAlternativeSymbolRepository;
use App\Repository\Trading212PieMetaDataRepository;
use App\Service\Trading212\PieService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[
	AsCommand(
		name: 'trading212:get-pie',
		description: 'Get all shares in trading212 pie with pie id'
	)
]
class GetPieCommand extends Command
{
	public function __construct(
		protected HttpClientInterface $client,
		protected ApiKeyRepository $apiKeyRepository,
		protected Trading212PieMetaDataRepository $trading212PieMetaDataRepository,
		protected TickerAlternativeSymbolRepository $tickerAlternativeSymbolRepository,
		protected EntityManagerInterface $entityManager
	) {
		parent::__construct();
	}

	protected function configure(): void
	{
		$this->addArgument(
			'pie-id',
			InputArgument::REQUIRED,
			'Pie id (int) to get data from. Use get-pies command to get available id\'s'
		);
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
		$pieID = $input->getArgument('pie-id');

		$io->note(sprintf('You passed an argument: %s', $pieID));
		$data = $pieService->getPie($pieID);
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

                $trading212PieInstrument->setTrading212PieMetaData($trading212PieMetaData);
				/**
				 * @var \App\Entity\TickerAlternativeSymbol
				 */
				$tickerAlternativeSymbol = $this->tickerAlternativeSymbolRepository->findOneBy(['symbol'=>$instrument['ticker']]);
				if ($tickerAlternativeSymbol) {
					$trading212PieInstrument->setTicker($tickerAlternativeSymbol->getTicker());
				}
				$this->entityManager->persist($trading212PieInstrument);
			}

			$this->entityManager->flush();

			$io->success(
				'Pie '.$trading212_pie_name. ' sucessfully saved'
			);
		}else {
            $io->warning('First make sure you have the pie meta data: run php bin/console trading212:get-pies');
        }
		return Command::SUCCESS;
	}
}
