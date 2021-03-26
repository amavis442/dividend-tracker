<?php

namespace App\Model;

use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ExchangeRateModel
{
    public const EXCHANGERATE_API = 'https://api.exchangeratesapi.io/latest';

    private $exchangerateCache;
    private $client;

    public function __construct(HttpClientInterface $client, CacheInterface $exchangerateCache)
    {
        $this->exchangerateCache = $exchangerateCache;
        $this->client = $client;
    }
    
    /**
     * Get the exchangerates from an external source and only refresh 1 per hour
     *
     * @return array
     */
    public function getRates(): array 
    {
        $client = $this->client;
        $apiCallUrl = self::EXCHANGERATE_API;

        $data = $this->exchangerateCache->get('exchangerates', function(ItemInterface $item) use ($client, $apiCallUrl){
            $item->expiresAfter(3600);
            $response = $client->request(
                'GET',
                $apiCallUrl
            );
            $content = $response->toArray();

            return $content;
        });
        //$this->exchangerateCache->delete('exchangerates');
        return $data['rates'] ?? [];
    }
}
