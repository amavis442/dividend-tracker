<?php

namespace App\Service\ExchangeRate;

use DOMDocument;
use DOMXPath;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ExchangeRateService implements ExchangeRateInterface
{
    public const ECB_EXCHANGERATE = 'https://api.exchangerate.host/latest?base=EUR';

    private $exchangerateCache;
    private $client;

    public function __construct(HttpClientInterface $client, CacheInterface $exchangerateCache)
    {
        $this->exchangerateCache = $exchangerateCache;
        $this->client = $client;
    }

    public function getRates(): array
    {
        $apiCallUrl = self::ECB_EXCHANGERATE;
        $client = $this->client;

        $data = $this->exchangerateCache->get('exchangerates', function (ItemInterface $item) use ($client, $apiCallUrl) {
            $item->expiresAfter(3600);
            $response = $client->request(
                'GET',
                $apiCallUrl
            );
            $content = $response->getContent(false);
            return $content;
        });

        $rates = [];
        if (false !== $data) {
            $response = json_decode($data);
            if ($response == null) {
                throw new \RuntimeException("Data for exchangerate is empty or can not be read");
            }

            if ($response->success === true) {
                $rates = (array) $response->rates;
            }
        }
        $rates['GBX'] = $rates['GBP'] * 100;

        return $rates;
    }
}
