<?php

namespace App\Service\ExchangeRate;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class WisselkoersNlExchangeRateService implements ExchangeRateInterface
{
	public const URL_EXCHANGERATE = 'https://www.wisselkoers.nl/calcvalutas.ashx';

	public function __construct(
		private HttpClientInterface $client,
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

 			return $rates;
		}

		$apiCallUrl = self::URL_EXCHANGERATE;
		$client = $this->client;

		$content = '';
		$response = $client->request('GET', $apiCallUrl);
		if ($response->getStatusCode() === 200) {
			$content = $response->getContent(false);
		}

		$rates = [];
        if (false !== $content) {
            $response = json_decode($content, true);
            if ($response == null) {
                throw new \RuntimeException("Data for exchangerate is empty or can not be read");
            }
			foreach ($response as $data) {
				if ($data['Shortname'] != "") {
					$rates[strtoupper($data['Shortname'])] = (float)str_replace(",",".",$data['Last']);
				}
			}
		}

		$rates['GBX'] = $rates['GBP'] * 100;
		if (!isset($rates['GB']) && isset($rates['GBP'])) {
			$rates['GB'] = $rates['GBP'];
		}

		$item = $this->exchangerateCache->getItem(
				ExchangeRateInterface::CACHE_KEY
			);
		$item->expiresAfter(60 * 15);
		$item->set($rates);

		$this->exchangerateCache->save($item);

		return $rates;
	}
}
