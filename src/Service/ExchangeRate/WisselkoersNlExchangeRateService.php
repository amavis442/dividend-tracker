<?php

namespace App\Service\ExchangeRate;

use App\Contracts\ExchangeRate\ExchangeRateInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\Service\ResetInterface;

class WisselkoersNlExchangeRateService implements ExchangeRateInterface, ResetInterface
{
    /** @var array<string, float>|null rates of the current request */
    private ?array $rates = null;

    public const URL_EXCHANGERATE = 'https://www.wisselkoers.nl/calcvalutas.ashx';

    public function __construct(
        private HttpClientInterface $client,
        private CacheItemPoolInterface $exchangerateCache
    ) {
    }

    public function reset(): void
    {
        $this->rates = null;
    }

    public function getRates(): array
    {
        return $this->rates ??= $this->loadRates();
    }

    private function loadRates(): array
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
        if ($content != '') {
            $response = json_decode($content, true);
            if ($response == null) {
                throw new \RuntimeException(
                    'Data for exchangerate is empty or can not be read'
                );
            }
            foreach ($response as $data) {
                if ($data['Shortname'] != '') {
                    $rates[
                        strtoupper($data['Shortname'])
                    ] = (float) str_replace(',', '.', $data['Last']);
                }
            }
        }

        if (!isset($rates['GBP'])) {
            // Incomplete rates are not worth keeping: try again on the next request.
            return $rates;
        }

        $rates['GBX'] = $rates['GBP'] * 100;
        $rates['GB'] ??= $rates['GBP'];

        $item = $this->exchangerateCache->getItem(
            ExchangeRateInterface::CACHE_KEY
        );
        $Minutes15 = 60 * 15;
        $item->expiresAfter($Minutes15);
        $item->set($rates);

        $this->exchangerateCache->save($item);

        return $rates;
    }
}
