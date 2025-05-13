<?php

namespace App\Service\DividendDate;

use App\Contracts\Service\DividendDatePluginInterface;

class IncomeSharesService extends AbstractDividendDate implements DividendDatePluginInterface
{
	public const API_URL = 'https://stage.incomeshares.com/api/products/{ISIN}';

	public function getData(string $symbol, string $isin): ?array
	{
		$url = str_replace('{ISIN}', strtoupper(trim($isin)), self::API_URL);
		$currency = 'USD';

		$response = $this->client->request('GET', $url, [
			'auth_bearer' => $this->apiKey,
		]);

		$content = $response->getContent(true);
		$content = str_replace("\xEF\xBB\xBF", '', $content);

		$data = json_decode($content, true);

		$rows = $data['data']['distributions'][0]['distributionCalendarModels'];

		$items = [];
		foreach ($rows as $row) {
            if (!$row['distributionPerShare'])
                continue;

			$item = [];
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
