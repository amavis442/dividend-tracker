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

		$pool = new FilesystemAdapter();
		$data = $pool->get('nasdaq', function (ItemInterface $item): array {
			$item->expiresAfter(3600);

			// ... do some HTTP request or heavy computations
			$response = $this->client->request('GET', self::URL);

			if ($response->getStatusCode() !== 200) {
				return [];
			}

			$content = $response->getContent(true);
			$data = json_decode($content, true);

			$currentYear = (int) date('Y');
			$records = [];
			foreach ($data['data']['calendar']['rows'] as $divDate) {
				$symbolData = strtolower($divDate['symbol']);
				$payDate = new \DateTime($divDate['payment_Date']);
				if (
					$divDate['dividend_Ex_Date'] == '' ||
					$divDate['payment_Date'] == '' ||
					$divDate['dividend_Rate'] == '' ||
					$currentYear < (int) $payDate->format('Y')
				) {
					continue;
				}

				$exDivDate = new \DateTime($divDate['dividend_Ex_Date']);
				$paymentDate = new \DateTime($divDate['payment_Date']);

				$declarationDate = new \DateTime($divDate['announcement_Date']);
				$recordDate = new \DateTime($divDate['record_Date']);
				$dividend = $divDate['dividend_Rate'];

				$record = [];
				$record['DeclaredDate'] = $declarationDate->format('Y-m-d');
				$record['RecordDate'] = $recordDate->format('Y-m-d');
				$record['ExDate'] = $exDivDate->format('Y-m-d');
				$record['PayDate'] = $paymentDate->format('Y-m-d');
				$record['DividendAmount'] = $dividend;
				$record['Type'] = Calendar::REGULAR;
				$record['Currency'] = 'USD';
				$records[$symbolData][] = $record;
			}
			return $records;
		});

		if (!isset($data[$symbol])) {
			return [];
		}

		return $data[$symbol];
	}
}
