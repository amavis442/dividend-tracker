<?php

namespace App\Service\DividendDate;

use App\Contracts\Service\DividendDatePluginInterface;
use App\Entity\Calendar;
use RuntimeException;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class FinancialModelingPrepService implements DividendDatePluginInterface
{
	public const URL = 'https://financialmodelingprep.com/api/v3/historical-price-full/stock_dividend/[SYMBOL]?apikey=[API_KEY]'; // source https://github.com/AlmaWeb3/dividend-web

	private array $ignore = ['QQQY'];


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
		if (in_array(strtolower($symbol), $this->ignore)) {
			return [];
		}

		// API key is only usefull for US stocks so we ignore the rest
		if (!stripos($isin, 'us')) {
			return [];
		}

		$url = '';
		$url = str_replace('[SYMBOL]', $symbol, self::URL);
		$url = str_replace('[API_KEY]', (string) $this->apiKey, $url);

		$response = $this->client->request('GET', $url);

		if ($response->getStatusCode() !== 200) {
			return null;
		}
		$content = $response->getContent(true);
		$data = json_decode($content);

        $currentYear = (int)date('Y');
		$items = [];
		foreach ($data->historical as $divDate) {
            $payDate = new \DateTime($divDate->paymentDate);
            if ($divDate->date == '' || $divDate->paymentDate == '' || $divDate->dividend == '' || $currentYear < (int)$payDate->format('Y')) {
                continue;
            }
			$item = [];
			$item['DeclaredDate'] = $divDate->declarationDate;
			$item['RecordDate'] = $divDate->recordDate;
			$item['ExDate'] = $divDate->date;
			$item['PayDate'] = $divDate->paymentDate;
			$item['DividendAmount'] = $divDate->dividend;
			$item['Type'] = Calendar::REGULAR;
			$item['Currency'] = 'USD';
			$items[] = $item;
		}
		return $items;
	}
}
