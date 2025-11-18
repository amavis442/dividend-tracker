<?php

namespace App\Service\DividendDate;

use App\Contracts\Service\DividendDatePluginInterface;
use App\Entity\Calendar;

class MassiveService extends AbstractDividendDate implements DividendDatePluginInterface
{
	public const URL = 'https://api.massive.com/v3/reference/dividends?ticker=[SYMBOL]&order=desc&limit=5&sort=ex_dividend_date&apiKey=[API_KEY]';

	private array $ignore = ['QQQY'];

	public function getData(string $symbol, string $isin): ?array
	{
		if (in_array(strtolower($symbol), $this->ignore)) {
			return [];
		}

		// API key is only usefull for US stocks so we ignore the rest
		if (stripos($isin, 'us') === false) {
			return [];
		}

		$url = '';
		$url = str_replace('[SYMBOL]', $symbol, static::URL);
		$url = str_replace('[API_KEY]', (string) $this->apiKey, $url);

		$response = $this->client->request('GET', $url);

		if ($response->getStatusCode() !== 200) {
			return null;
		}
		$content = $response->getContent(true);
		$data = json_decode($content);

        $currentYear = (int)date('Y');
		$items = [];
		foreach ($data->results as $divDate) {
            $payDate = new \DateTime($divDate->pay_date);
            if ($divDate->ex_dividend_date == '' || $divDate->pay_date == '' || $divDate->cash_amount == '' || $currentYear < (int)$payDate->format('Y')) {
                continue;
            }
			$item = [];
			$item['DeclaredDate'] = $divDate->ex_dividend_date;
			$item['RecordDate'] = $divDate->record_date;
			$item['ExDate'] = $divDate->ex_dividend_date;
			$item['PayDate'] = $divDate->pay_date;
			$item['DividendAmount'] = $divDate->cash_amount;
			$item['Type'] = Calendar::REGULAR;
			$item['Currency'] = 'USD';
			$items[] = $item;
		}
		return $items;
	}
}
