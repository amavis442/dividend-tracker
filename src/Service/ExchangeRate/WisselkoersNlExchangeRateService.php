<?php

namespace App\Service\ExchangeRate;

use App\Contracts\ExchangeRate\ExchangeRateInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\Service\ResetInterface;

class WisselkoersNlExchangeRateService implements ExchangeRateInterface, ResetInterface
{
    private const RATES_LIFETIME = 60 * 15;
    private const INCOMPLETE_RATES_LIFETIME = 60;

    /** @var array<string, float>|null last fetched rates */
    private ?array $rates = null;
    private int $ratesExpireAt = 0;

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
        if ($this->rates !== null && time() < $this->ratesExpireAt) {
            return $this->rates;
        }

        $this->rates = $this->loadRates();
        $lifetime = $this->isComplete($this->rates) ? self::RATES_LIFETIME : self::INCOMPLETE_RATES_LIFETIME;
        $this->ratesExpireAt = time() + $lifetime;

        return $this->rates;
    }

    /**
     * @param array<string, float> $rates
     */
    private function isComplete(array $rates): bool
    {
        return ($rates['GBP'] ?? 0.0) > 0.0;
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
            if (!is_array($response)) {
                throw new \RuntimeException('Data for exchangerate has an unexpected format');
            }
            foreach ($response as $data) {
                if (!is_array($data) || !isset($data['Shortname'], $data['Last'])) {
                    throw new \RuntimeException('Data for exchangerate has an unexpected format');
                }
                if ($data['Shortname'] != '') {
                    $rates[
                        strtoupper($data['Shortname'])
                    ] = (float) str_replace(',', '.', $data['Last']);
                }
            }
        }

        if (!$this->isComplete($rates)) {
            // Incomplete rates are not worth keeping: try again soon.
            return $rates;
        }

        $rates['GBX'] = $rates['GBP'] * 100;
        $rates['GB'] ??= $rates['GBP'];

        $item = $this->exchangerateCache->getItem(
            ExchangeRateInterface::CACHE_KEY
        );
        $item->expiresAfter(self::RATES_LIFETIME);
        $item->set($rates);

        $this->exchangerateCache->save($item);

        return $rates;
    }
}
