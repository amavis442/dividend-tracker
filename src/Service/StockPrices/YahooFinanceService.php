<?php

namespace App\Service\StockPrices;

use App\Contracts\Service\StockPricePluginInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class YahooFinanceService implements StockPricePluginInterface
{
    public const YAHOO_API = 'https://query1.finance.yahoo.com/v8/finance/chart/';
    public const YAHOO_URL = 'https://finance.yahoo.com/quote/';
    public const YAHOO_QUOTE = 'https://query1.finance.yahoo.com/v6/finance/quote?symbols=';

    public $translate = [
        'EQQQ' => 'EQQQ.MI',
        'ISF' => 'ISFA.AS',
        'EMIM' => 'EMIM.AS',
        'IWDP' => 'IWDP.AS',
        'IPRP' => 'IPRP.AS',
        'INRG' => 'INRG.L',
        'SGLN' => 'IGLN.L',
        'IUS3' => 'ISP6.L',
        'NESN' => 'NESN.SW',
        'SIE' => 'SIE.DE',
        'ISPA' => 'ISPA.DE',
        'VMID' => 'VMID.L',
        'UNA' => 'UNA.AS',
        'VEUR' => 'VEUR.AS',
        'VAPX' => 'VAPX.L',
        'VJPN' => 'VJPN.SW',
        'WHEA' => 'WHEA.L',
        'ECAR' => 'ECAR.L',
        'VUSA' => 'VUSA.L',
        'WKL' => 'WKL.AS',
        'SPX' => 'SPX.L',
        'CRDA' => 'CRDA.L',
        'BN' => 'BN.PA',
        'DGE' => 'DGE.L',
        'FRE' => 'FRE.DE',
        'FME' => 'FME.DE',
        'HLMA' => 'HLMA.L',
        'HEN' => 'HEN.DE',
        'PHIA' => 'PHIA.AS',
        'RMS' => 'RMS.PA',
        'LISN' => 'LISN.SW',
        'MUV2' => 'MUV2.MI',
        'REE' => 'REE.MC'
    ];

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
                if (isset($this->translate[$symbol])) {
                    $symbol = $this->translate[$symbol];
                }
                return strtoupper($symbol);
            }, array_values($symbols)))
        );
        $translate = array_flip($this->translate);

        $result = [];
        if ($response->getStatusCode() === 200) {
            $content = $response->toArray();
            if (isset($content['quoteResponse']) && isset($content['quoteResponse']['result'])) {
                if (isset($content['quoteResponse']) && $content['quoteResponse']['error'] == null) {
                    $symbolData = $content['quoteResponse']['result'];
                    foreach ($symbolData as $data) {
                        if (isset($data['currency'])) {
                            if (isset($translate[$data['symbol']])) {
                                $data['symbol'] = $translate[$data['symbol']];
                            }
                            if ($data['currency'] === 'GBp') {
                                $data['currency'] = 'GBX';
                            }
                            $result[$data['symbol']] = $data;
                        }
                    }
                }
            }
        }

        return $result;
    }
}