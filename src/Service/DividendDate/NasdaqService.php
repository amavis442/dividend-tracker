<?php

namespace App\Service\DividendDate;

use App\Contracts\Service\DividendDatePluginInterface;
use App\Entity\Calendar;
use RuntimeException;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class NasdaqService implements DividendDatePluginInterface
{
	public const URL = 'https://api.nasdaq.com/api/calendar/dividends';
	public const API_URL = 'https://api.nasdaq.com/api/quote/[SYMBOL]/dividends?assetclass=stocks';

	private array $ignore = [];

	/**
	 * Http client
	 *
	 * @var HttpClientInterface
	 */
	protected $client;
	protected $apiKey;

	public function __construct(HttpClientInterface $client)
	{
		$this->client = $client;
	}

	public function setApiKey(?string $apiKey): void
	{
		$this->apiKey = $apiKey;
	}

	public function getData(string $symbol, string $isin): ?array
	{
		$symbol = strtolower($symbol);

		if (in_array($symbol, $this->ignore)) {
			return [];
		}
		// Try the api
		return $this->apiCall($symbol);
	}

	protected function apiCall($symbol): array
	{
		$apiUrl = str_replace('[SYMBOL]', strtoupper($symbol), self::API_URL);

		$response = $this->client->request('GET', $apiUrl);

		if ($response->getStatusCode() !== 200) {
			return [];
		}

		$content = $response->getContent(true);
		$jsonData = json_decode($content, true);

		$records = [];
		if ($jsonData['data'] == null) {
			//dump($jsonData['status']);
		}
		if (
			$jsonData['data'] != null &&
			count($jsonData['data']['dividends']['rows']) > 0
		) {
			foreach ($jsonData['data']['dividends']['rows'] as $divDate) {
				/*
			"exOrEffDate": "01/02/2025",
			"type": "Cash",
			"amount": "$0.15",
			"declarationDate": "12/11/2024",
			"recordDate": "01/02/2025",
			"paymentDate": "01/07/2025",
			"currency": "USD"
			*/
				if (
					$divDate['exOrEffDate'] == '' ||
					$divDate['exOrEffDate'] == 'N/A' ||
					$divDate['paymentDate'] == '' ||
					$divDate['paymentDate'] == 'N/A' ||
					$divDate['amount'] == ''

				) {
					return [];
				}

				try {
				$exDivDate = new \DateTime($divDate['exOrEffDate']);
				} catch (\Exception $e) {
					dump($divDate);
					throw $e;
				}
				$paymentDate = new \DateTime($divDate['paymentDate']);

				$declarationDate = new \DateTime($divDate['declarationDate']);
				$recordDate = new \DateTime($divDate['recordDate']);
				$dividend = str_replace('$', '', $divDate['amount']);

				$record = [];
				$record['DeclaredDate'] = $declarationDate->format('Y-m-d');
				$record['RecordDate'] = $recordDate->format('Y-m-d');
				$record['ExDate'] = $exDivDate->format('Y-m-d');
				$record['PayDate'] = $paymentDate->format('Y-m-d');
				$record['DividendAmount'] = $dividend;
				$record['Type'] = Calendar::REGULAR;
				$record['Currency'] = 'USD';
				$records[] = $record;
			}
		}
		return $records;
	}
}
