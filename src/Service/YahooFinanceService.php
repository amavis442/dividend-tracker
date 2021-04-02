<?php

namespace App\Service;

use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class YahooFinanceService
{
    public const YAHOO_API = 'https://query1.finance.yahoo.com/v8/finance/chart/';
    public const YAHOO_URL = 'https://finance.yahoo.com/quote/';

    private $yahooCache;
    private $client;
    private $exchangeRateService;

    public function __construct(HttpClientInterface $client, CacheInterface $yahooCache, ExchangeRateService $exchangeRateService)
    {
        $this->yahooCache = $yahooCache;
        $this->client = $client;
        $this->exchangeRateService = $exchangeRateService;
    }

    /**
     * Get the exchangerates from an external source and only refresh 1 per hour
     *
     * @return array
     */
    public function getData(string $symbol): array
    {
        $client = $this->client;
        $apiCallUrl = self::YAHOO_API;

        $data = $this->yahooCache->get('yahoo_'.strtolower($symbol), function (ItemInterface $item) use ($client, $apiCallUrl, $symbol) {
            $item->expiresAfter(3600);
            $response = $client->request(
                'GET',
                $apiCallUrl . strtoupper($symbol)
            );

            $content = [];
            if ($response->getStatusCode() === 200) {
                $content = $response->toArray();
            }
            return $content;
        });
        
        //$this->yahooCache->delete('yahoo_'.strtolower($symbol));
        
        return $data ?? [];
    }



    public function getQuote(string $symbol): ?float
    {
        $rates = $this->exchangeRateService->getRates();
        $data = $this->getData($symbol);
        $result = 0.0;  
        $currency = 'USD';  
        if (isset($data['chart']) && isset($data['chart']['result'][0]['meta']['regularMarketPrice'])) {
            if (isset($data['chart']) && $data['chart']['error'] == null) {
                $symbolData = $data['chart']['result'][0]['meta'];
                $result = $symbolData['regularMarketPrice'];
                $currency = $symbolData['currency'];
            } else {
                $result = null;
            }
        }
        return $result / ($rates[$currency ?? 'USD']);
    }
}
