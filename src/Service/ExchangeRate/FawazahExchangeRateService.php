<?php

namespace App\Service\ExchangeRate;

use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class FawazahExchangeRateService implements ExchangeRateInterface
{
    public const USD_EXCHANGERATE = 'https://cdn.jsdelivr.net/gh/fawazahmed0/currency-api@1/latest/currencies/eur/usd.json';
    public const GPB_EXCHANGERATE = 'https://cdn.jsdelivr.net/gh/fawazahmed0/currency-api@1/latest/currencies/eur/gbp.json';

    private $exchangerateCache;
    private $client;

    public function __construct(HttpClientInterface $client, CacheInterface $exchangerateCache)
    {
        $this->exchangerateCache = $exchangerateCache;
        $this->client = $client;
    }

    public function getRates(): array
    {
        $apiCallUrl['usd'] = self::USD_EXCHANGERATE;
        $apiCallUrl['gbp'] = self::GPB_EXCHANGERATE;

        $client = $this->client;

        $data = $this->exchangerateCache->get('exchangerates', function (ItemInterface $item) use ($client, $apiCallUrl) {
            $item->expiresAfter(3600);
            $response = $client->request(
                'GET',
                $apiCallUrl['usd'],
                [
                    'json' => true,
                    'verify_peer' => false,
                    'verify_host' => false
                ] // Needed t o solve "SSL: no alternative certificate subject name matches target host name" error
            );
            $content['usd'] = $response->getContent(false);
            $response = $client->request(
                'GET',
                $apiCallUrl['gbp'],
                [
                    'json' => true,
                    'verify_peer' => false,
                    'verify_host' => false
                ]
            );
            $content['gbp'] = $response->getContent(false);
            return $content;
        });


        $rates = [];
        if (false !== $data) {
            try {
                $response = json_decode($data['usd']);
                if (isset($response->usd)) {
                    $rates['USD'] = $response->usd;
                }
            } catch (\Exception $e) {
                throw $e;
            }

            try {
                $response = json_decode($data['gbp']);
                if (isset($response->gbp)) {
                    $rates['GBP'] = $response->gbp;
                }
            } catch (\Exception $e) {
                throw $e;
            }
        }
        $rates['GBX'] = $rates['GBP'] * 100;

        return $rates;
    }
}
