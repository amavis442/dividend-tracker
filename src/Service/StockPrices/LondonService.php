<?php

namespace App\Service\StockPrices;

use App\Contracts\Service\StockPricePluginInterface;
use DOMDocument;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class LondonService implements StockPricePluginInterface
{

    /**
     * Used for calling the api
     *
     * @var HttpClientInterface
     */
    private $client;

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
        HttpClientInterface $client
    ) {
        $this->client = $client;
    }

    public function getQuotes(array $symbols): ?array
    {
        $client = $this->client;
        $symbols = $this->retrieveableSymbols;

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
            $stockprices[$symbol] = ['currency' => $currency, 'regularMarketPrice' => (float) $marketPrice];
        }

        return $stockprices;
    }
}
