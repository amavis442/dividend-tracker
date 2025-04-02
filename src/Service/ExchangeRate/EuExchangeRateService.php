<?php

namespace App\Service\ExchangeRate;

use DOMDocument;
use DOMXPath;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Cache\CacheItem;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class EuExchangeRateService implements ExchangeRateInterface
{
	public const ECB_EXCHANGERATE = 'https://www.ecb.europa.eu/stats/policy_and_exchange_rates/euro_reference_exchange_rates/html/index.en.html';

	public function __construct(
		private HttpClientInterface $euClient,
		private CacheItemPoolInterface $exchangerateCache
	) {
	}

	public function getRates(): array
	{
		if (
			$this->exchangerateCache->hasItem(ExchangeRateInterface::CACHE_KEY)
		) {
			$item = $this->exchangerateCache->getItem(
				ExchangeRateInterface::CACHE_KEY
			);
			$rates = $item->get();
			if (!isset($rates['GB']) && isset($rates['GBP'])) {
				$rates['GB'] = $rates['GBP'];
			}
 			return $rates;
		}

		$apiCallUrl = self::ECB_EXCHANGERATE;
		$client = $this->euClient;

		$content = '';
		$response = $client->request('GET', $apiCallUrl);
		if ($response->getStatusCode() === 200) {
			$content = $response->getContent(false);
		}

		$headers = $response->getHeaders();
		if ($headers['content-encoding'][0] == 'gzip') {
			$content = gzdecode($content);
		}

		$internalErrors = libxml_use_internal_errors(true);
		$dom = new DOMDocument();
		$dom->loadHTML($content);
		$xpath = new DOMXPath($dom);
		$rates = $this->parseToArray($xpath, 'forextable');
		libxml_use_internal_errors($internalErrors);

		//dd($rates, $internalErrors);

		$rates['GBX'] = $rates['GBP'] * 100;
		$rates['GB'] = $rates['GBP'];

		$item = $this->exchangerateCache->getItem(
				ExchangeRateInterface::CACHE_KEY
			);
		$item->expiresAfter(60 * 30);
		$item->set($rates);

		$this->exchangerateCache->save($item);

		return $rates;
	}

	public function parseToArray($xpath, $class): array
	{
		$xpathquery = "//table[@class='" . $class . "']";
		$elements = $xpath->query($xpathquery);
		$resultarray = [];

		if (!is_null($elements)) {
			foreach ($elements as $element) {
				$nodes = $element->getElementsByTagName('tr');
				foreach ($nodes as $node) {
					$tdNodes = $node->getElementsByTagName('td');
					if ($tdNodes->count() > 0) {
						$currency = str_replace(
							"\n",
							'',
							$tdNodes[0]->nodeValue
						);
						$exchangeRate = str_replace(
							"\n",
							'',
							$tdNodes[2]->nodeValue
						);

						$resultarray[$currency] = (float)trim($exchangeRate);
					}
				}
			}
		}
		return $resultarray;
	}
}
