<?php

namespace App\Service\StockPrices;

use App\Contracts\Service\StockPriceInterface;
use App\Service\ExchangeRateService;
use DOMDocument;
use RuntimeException;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class LondonService implements StockPriceInterface
{

    /**
     * Do not call api everytime
     *
     * @var CacheInterface
     */
    private $stockCache;

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

    private $retrieveableSymbols = [
        'PNN' => 'https://www.londonstockexchange.com/stock/PNN/pennon-group-plc/company-page',
        'SEMB' => 'https://www.londonstockexchange.com/stock/SEMB/ishares/company-page',
        'RB' => 'https://www.londonstockexchange.com/stock/RKT/reckitt-benckiser-group-plc/company-page',
        'BATS' => 'https://www.londonstockexchange.com/stock/BATS/british-american-tobacco-plc/company-page',
        'SSHY' => 'https://www.londonstockexchange.com/stock/SSHY/pimco-etfs-public-limited-company/company-page',
        'STHS' => 'https://www.londonstockexchange.com/stock/STHS/pimco-etfs-public-limited-company/company-page',
        'VWRL' => 'https://www.londonstockexchange.com/stock/VWRL/vanguard/company-page',
        'VGOV' => 'https://www.londonstockexchange.com/stock/VGOV/vanguard/company-page',
        'VUSC' => 'https://www.londonstockexchange.com/stock/VUSC/vanguard/company-page',
    ];

    public function __construct(
        HttpClientInterface $client,
        CacheInterface $stockCache,
        ExchangeRateService $exchangeRateService
    ) {
        $this->stockCache = $stockCache;
        $this->client = $client;
        $this->exchangeRateService = $exchangeRateService;
    }

    public function getQuotes(array $symbols): ?array
    {
        $client = $this->client;
        $symbols = $this->retrieveableSymbols;

        $cacheTag = 'londonstockexchange';
        //$this->stockCache->delete($cacheTag);

        $data = $this->stockCache->get($cacheTag, function (ItemInterface $item) use ($client, $symbols) {
            $item->expiresAfter(1800);

            $stockprices = [];
            $responses = [];
            foreach ($symbols as $symbol => $url) {
                $responses[$symbol] = $client->request(
                    'GET',
                    $url
                );
            }

            foreach ($responses as $symbol => $response) {
                $marketPrice = 0.0;
                $currency = 'GBX';
                if ($response->getStatusCode() === 200) {
                    $content = $response->getContent();
                    $internalErrors = libxml_use_internal_errors(true);
                    $dom = new DOMDocument();
                    $dom->loadHTML($content);
                    $children = $dom->getElementsByTagName('span');

                    /**
                     * @var $child \DOMElement
                     */
                    foreach ($children as $child) {
                        if ($child->hasAttribute('class')) {
                            if ($child->getAttribute('class') == 'price-tag') {
                                $marketPrice = $child->nodeValue;
                                break;
                            }
                        }
                    }

                    $children = $dom->getElementsByTagName('div');
                    /**
                     * @var $child \DOMElement
                     */
                    foreach ($children as $child) {
                        if ($child->hasAttribute('class')) {
                            $classData = $child->getAttribute('class');
                            if (strpos($classData, 'currency-label') !== false) {
                                $currency = str_replace(['Price (', ')'], '', $child->nodeValue);
                                break;
                            }
                        }
                    }
                    libxml_use_internal_errors($internalErrors);
                }

                $marketPrice = trim(str_replace(',', '', $marketPrice));
                $divider = 1;
                if ($currency == 'GBX') {
                    $divider = 100;
                    $currency = 'GBP';
                }

                $stockprices[$symbol] = ['currency' => $currency, 'price' => (float) $marketPrice / $divider];
            }

            $stockprices['timestamp'] = time();
            return $stockprices;
        });

        $this->data = $data;

        return $data;
    }

    public function getMarketPrice(string $symbol): ?float
    {
        if (!$this->data) {
            throw new RuntimeException('Call LondonService::getQuotes(array $symbols) first');
        }

        $rates = $this->exchangeRateService->getRates();
        $marketPrice = 0.0;
        $currency = 'USD';

        if (isset($this->data[$symbol]) && isset($this->data[$symbol]['price'])) {
            $marketPrice = $this->data[$symbol]['price'];
            $currency = $this->data[$symbol]['currency'];
        } else {
            return null;
        }

        return $marketPrice / ($rates[$currency]);
    }

    public function getQuote(string $symbol): ?float
    {
        $rates = $this->exchangeRateService->getRates();
        $data = $this->data[$symbol];
        $price = $data['price'];
        $currency = $data['currency'];

        return $price / ($rates[$currency]);
    }
}
