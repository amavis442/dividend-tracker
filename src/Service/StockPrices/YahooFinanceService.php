<?php

namespace App\Service\StockPrices;

use App\Contracts\Service\StockPricePluginInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class YahooFinanceService implements StockPricePluginInterface
{
    public const YAHOO_API = 'https://query1.finance.yahoo.com/v8/finance/chart/';
    public const YAHOO_URL = 'https://finance.yahoo.com/quote/';
    public const YAHOO_QUOTE = 'https://query1.finance.yahoo.com/v7/finance/quote?symbols=';

    /**
     * Used for calling the api
     *
     * @var HttpClientInterface
     */
    private $client;

    public function __construct(
        HttpClientInterface $client
    ) {
        $this->client = $client;
    }

    public function getQuotes(array $symbols): ?array
    {
        $client = $this->client;
        $apiCallUrl = self::YAHOO_QUOTE;

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

        return $result;
    }
}
