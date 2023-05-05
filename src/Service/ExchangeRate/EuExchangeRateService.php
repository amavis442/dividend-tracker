<?php

namespace App\Service\ExchangeRate;

use DOMDocument;
use DOMXPath;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class EuExchangeRateService implements ExchangeRateInterface
{
    public const ECB_EXCHANGERATE = 'https://www.ecb.europa.eu/stats/policy_and_exchange_rates/euro_reference_exchange_rates/html/index.en.html';

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

        dd($data);
        $internalErrors = libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        $dom->loadHTML($data);
        $xpath = new DOMXPath($dom);
        $rates = $this->parseToArray($xpath, 'forextable');
        libxml_use_internal_errors($internalErrors);

        dd($rates, $internalErrors);

        $rates['GBX'] = $rates['GBP'] * 100;

        return $rates;
    }

    public function parseToArray($xpath, $class)
    {
        $xpathquery = "//table[@class='" . $class . "']";
        $elements = $xpath->query($xpathquery);
        $resultarray = [];

        if (!is_null($elements)) {
            foreach ($elements as $element) {
                $nodes = $element->getElementsByTagName('tr');
                foreach ($nodes as $node) {
                    $tdNodes = $node->getElementsByTagName('td');
                    if ($tdNodes->count() > 0) {
                        $currency = str_replace("\n", "", $tdNodes[0]->nodeValue);
                        $exchangeRate = str_replace("\n", "", $tdNodes[2]->nodeValue);

                        $resultarray[$currency] = $exchangeRate;
                    }
                }
            }
        }
        return $resultarray;
    }
}