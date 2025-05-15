<?php

namespace App\Service\DividendDate;

use App\Contracts\Service\DividendDatePluginInterface;
use DOMDocument;
use DOMXPath;

class IncomeSharesService extends AbstractDividendDate implements DividendDatePluginInterface
{
	public const API_URL = 'https://stage.incomeshares.com/api/products/{ISIN}';
	public $urls = [
		'TSLI' => 'https://incomeshares.com/en-eu/etps/income-shares-tesla-options-etp/', //tesla
		'AMZD' => 'https://incomeshares.com/en-eu/etps/income-shares-amazon-options-etp/', //amazon
		'GOOO' => 'https://incomeshares.com/en-eu/etps/income-shares-alphabet-options-etp/', //google
		'METI' => 'https://incomeshares.com/en-eu/etps/income-shares-meta-options-etp/', // facebook
		'ONVD' => 'https://incomeshares.com/en-eu/etps/income-shares-nvidia-options-etp/', //nvidia
		'APPL' => 'https://incomeshares.com/en-eu/etps/income-shares-apple-options-etp/', //apple
		'COIY' => 'https://incomeshares.com/en-eu/etps/income-shares-coinbase-options-etp/', // coinbase
		'YMSF' => 'https://incomeshares.com/en-eu/etps/income-shares-microsoft-options-etp/', // Microschrot
		'QQQY' => 'https://incomeshares.com/en-eu/etps/income-shares-nasdaq-100-options-etp/', // Nasdaq
		'SPYY' => 'https://incomeshares.com/en-eu/etps/income-shares-s-p-500-options-etp/', //SP500
		'GLDE' => 'https://incomeshares.com/en-eu/etps/income-shares-s-p-500-options-etp/',  //Gold

	];

	public function getUrl(string $symbol): string
	{
		return $this->urls[$symbol];
	}

	public function getData(string $symbol, string $isin): ?array
	{
		$url = $this->urls[$symbol];
        $response = $this->client->request(
            'GET',
            $url
        );
		if ($response->getStatusCode() === 200) {
			$content = $response->getContent(false);
		}

		$headers = $response->getHeaders();
		/*if ($headers['content-encoding'][0] == 'gzip') {
			$result = gzdecode($content);
			if ($result) {
				$content = $result;
			}
		}*/


		$internalErrors = libxml_use_internal_errors(true);
		$dom = new DOMDocument();
		$dom->loadHTML($content);
		$xpath = new DOMXPath($dom);
		$dividendDates = $this->parseToArray($xpath, 'distributionCalendar');
		libxml_use_internal_errors($internalErrors);

		$currency = 'USD';
		$items = [];

		foreach ($dividendDates as $row) {
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

	public function parseToArray(DOMXPath $xpath, string $class): array
	{
		$xpathquery = "//div[@id='" . $class . "']";
		$elements = $xpath->query($xpathquery);
		$resultarray = [];

		if (!is_null($elements)) {
			/**
			 * @var \DOMDocument $element
			 */
			foreach ($elements as $element) {
				$nodes = $element->getElementsByTagName('tr');
				foreach ($nodes as $node) {
					$tdNodes = $node->getElementsByTagName('td');
					if ($tdNodes->count() > 0) {

						$declarationDate = str_replace(
							"\n",
							'',
							$tdNodes[0]->nodeValue
						);
						$exDate = str_replace(
							"\n",
							'',
							$tdNodes[1]->nodeValue
						);
						$recordDate = str_replace(
							"\n",
							'',
							$tdNodes[2]->nodeValue
						);
						$paymentDate = str_replace(
							"\n",
							'',
							$tdNodes[3]->nodeValue
						);
						$distributionPerShare = str_replace(
							"\n",
							'',
							$tdNodes[4]->nodeValue
						);
						$distributionPerShare = trim($distributionPerShare);
						$currency = substr($distributionPerShare, 0, 1);

						$resultarray[] = [
							'declarationDate' => (new \DateTime($declarationDate))->format('Y-m-d'),
							'exDate' => (new \DateTime($exDate))->format('Y-m-d'),
							'recordDate' => (new \DateTime($recordDate))->format('Y-m-d'),
							'paymentDate' => (new \DateTime($paymentDate))->format('Y-m-d'),
							'distributionPerShare' => (float)str_replace('$','', $distributionPerShare),
							'currency' => $currency,
						];
					}
				}
			}
		}
		return $resultarray;
	}
}
