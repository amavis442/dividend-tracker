<?php

namespace App\Service\DividendDate;

use App\Contracts\Service\DividendDatePluginInterface;
use App\Entity\Calendar;

class FinancialModelingPrepService extends AbstractDividendDate implements DividendDatePluginInterface
{
	public const URL = 'https://financialmodelingprep.com/stable/dividends?symbol=[SYMBOL]&apikey=[API_KEY]';

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
		foreach ($data as $divDate) {
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
