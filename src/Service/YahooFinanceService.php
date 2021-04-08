<?php

namespace App\Service;

use RuntimeException;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class YahooFinanceService
{
    public const YAHOO_API = 'https://query1.finance.yahoo.com/v8/finance/chart/';
    public const YAHOO_URL = 'https://finance.yahoo.com/quote/';
    public const YAHOO_QUOTE = 'https://query1.finance.yahoo.com/v7/finance/quote?symbols=';

    /**
     * Do not call api everytime
     *
     * @var CacheInterface
     */
    private $yahooCache;

    /**
     * Used for calling the api
     *
     * @var HttpClientInterface
     */
    private $client;

    /**
     * Get the exchange rates
     *
     * @var ExchangeRateService
     */
    private $exchangeRateService;

    /**
     * Data from the last call to the api
     *
     * @var array
     */
    private $data;

    public function __construct(
        HttpClientInterface $client,
        CacheInterface $yahooCache,
        ExchangeRateService $exchangeRateService
    ) {
        $this->yahooCache = $yahooCache;
        $this->client = $client;
        $this->exchangeRateService = $exchangeRateService;
    }

    public function getQuotes(array $symbols, string $tag)
    {
        $client = $this->client;
        $apiCallUrl = self::YAHOO_QUOTE;

        $tagName = 'yahoo_quotes_' . $tag;
        $data = $this->yahooCache->get($tagName, function (ItemInterface $item) use ($client, $apiCallUrl, $symbols) {
            $item->expiresAfter(300);
            $response = $client->request(
                'GET',
                $apiCallUrl . implode(',', array_map(function ($symbol) {
                    return strtoupper($symbol);
                }, array_values($symbols)))
            );

            $result = [];
            if ($response->getStatusCode() === 200) {
                $content = $response->toArray();
                if (isset($content['quoteResponse']) && isset($content['quoteResponse']['result'])) {
                    if (isset($content['quoteResponse']) && $content['quoteResponse']['error'] == null) {
                        $symbolData = $content['quoteResponse']['result'];
                        foreach ($symbolData as $data) {
                            $result[$data['symbol']] = $data;
                        }
                    }
                }
            }
            $result['timestamp'] = time();

            return $result;
        });

        //$this->yahooCache->delete($tagName);
        $this->data = $data;

        return $data;
    }

    /**
     * Get marketprice for symbol
     *
     * @param string $symbol
     * @return float
     */
    public function getMarketPrice(string $symbol): ?float
    {
        if (!$this->data) {
            throw new RuntimeException('Call YahooFinanceService::getQuotes(array $symbols) first');
        }

        $rates = $this->exchangeRateService->getRates();
        $marketPrice = 0.0;
        $currency = 'USD';

        if (isset($this->data[$symbol]) && isset($this->data[$symbol]['regularMarketPrice'])) {
            $marketPrice = $this->data[$symbol]['regularMarketPrice'];
            $currency = $this->data[$symbol]['currency'];
        } else {
            return null;
        }

        return $marketPrice / ($rates[$currency]);
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

        $data = $this->yahooCache->get('yahoo_' . strtolower($symbol), function (ItemInterface $item) use ($client, $apiCallUrl, $symbol) {
            $item->expiresAfter(3600);
            $response = $client->request(
                'GET',
                $apiCallUrl . strtoupper($symbol)
            );

            $marketPrice = 0.0;
            $currency = 'USD';
            if ($response->getStatusCode() === 200) {
                $content = $response->toArray();

                if (isset($content['chart']) && isset($content['chart']['result'][0]['meta']['regularMarketPrice'])) {
                    if (isset($content['chart']) && $content['chart']['error'] == null) {
                        $symbolData = $content['chart']['result'][0]['meta'];
                        $marketPrice = $symbolData['regularMarketPrice'];
                        $currency = $symbolData['currency'];
                    }
                }
            }

            return ['currency' => $currency, 'price' => $marketPrice];
        });

        //$this->yahooCache->delete('yahoo_'.strtolower($symbol));

        return $data;
    }

    public function getQuote(string $symbol): ?float
    {
        $rates = $this->exchangeRateService->getRates();
        $data = $this->getData($symbol);
        $price = $data['price'];
        $currency = $data['currency'];

        return $price / ($rates[$currency]);
    }
}
