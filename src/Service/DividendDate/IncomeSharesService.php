<?php

namespace App\Service\DividendDate;

use App\Contracts\Service\DividendDatePluginInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

use function Symfony\Component\DependencyInjection\Loader\Configurator\env;

class IncomeSharesService implements DividendDatePluginInterface
{
	public const API_URL = 'https://stage.incomeshares.com/api/products/{ISIN}';

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
		$url = str_replace('{ISIN}', strtoupper(trim($isin)), self::API_URL);
		$currency = 'USD';

		$response = $this->client->request('GET', $url, [
			'auth_bearer' => $this->apiKey, // '6|eB2Kw0VPdjDc6zebi2wTqGStBKmeDyTdMkMwudWZc1849cdf',
		]);

		$content = $response->getContent(true);
		$content = str_replace("\xEF\xBB\xBF", '', $content);

		/*
        $bom = pack('H*','EFBBBF');
        $content = preg_replace("/^$bom/", '', $content);
         */
		$data = json_decode($content, true);

		$rows = $data['data']['distributions'][0]['distributionCalendarModels'];

		$items = [];
		foreach ($rows as $row) {
            if (!$row['distributionPerShare'])
                continue;

			$item = [];
			//$item['DeclaredDate'] = $row[0][0]['raw'];
			$item['RecordDate'] = $row['recordDate'];
			$item['ExDate'] = $row['exDate'];
			$item['PayDate'] = $row['paymentDate'];
			$item['DividendAmount'] = $row['distributionPerShare'];
			$item['Type'] = 'Distribution';
			$item['Currency'] = $currency; //strpos('USD', $row[0][4]['display'])
			$items[] = $item;
		}
		return $items;
	}
}
