<?php

namespace App\Service\DividendDate;

use App\Contracts\Service\DividendDatePluginInterface;
use DateTime;
use RuntimeException;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

class SeekingAlphaService implements DividendDatePluginInterface
{
    public const TOKEN_URL = "https://seekingalpha.com/market_data/xignite_token";
    public const URL = "https://globalhistorical.xignite.com/";

    public $translate = [
        'NESN' => 'NSRGY',
    ];

    /**
     * Http client
     *
     * @var HttpClientInterface
     */
    private $client;
    /**
     *
     * @var string
     */
    private $usedUrl = '';
    private $dividenData = [];

    public function __construct(HttpClientInterface $client)
    {
        $this->client = $client;
    }

    private function getToken()
    {
        $response = $this->client->request('GET', self::TOKEN_URL);
        if ($response->getStatusCode() !== 200) {
            throw new RuntimeException("Status code: " . $response->getStatusCode() . '::Can not get token...' . $response->getContent());
        }
        //$token['_token'] = "fd1f4a4e836e3c67486c178d82228ff7d616c6af732d8d8de92c602095f9e20e6986177b9977a842a1fea60908ec3a74c1af6678";
        //$token['_token_userid'] = 122;
        //{"_token":"fd1f4a4e836e3c67486c178d82228ff7d616c6af732d8d8de92c602095f9e20e6986177b9977a842a1fea60908ec3a74c1af6678","_token_userid":122}
        //return $token;
        return $response->toArray();
    }

    private function getStockHistory(array $token, string $ticker)
    {
        $url = "https://globalhistorical.xignite.com/v3/xGlobalHistorical.json/GetCashDividendHistory?"
            . "IdentifierType=Symbol&Identifier=" . $ticker
            . "&StartDate=01/01/" . (date('Y') - 3) . "&EndDate=12/30/" . (date('Y') + 3) . "&"
            . "IdentifierAsOfDate=&CorporateActionsAdjusted=true&_token="
            . $token['_token'] . "&_token_userid=" . $token['_token_userid'];
        $this->usedUrl = $url;

        $response = $this->client->request('GET', $url);
        if ($response->getStatusCode() !== 200) {
            throw new RuntimeException('Can not get token...');
        }
        $data = $response->toArray();
        if ($data['Outcome'] == 'Success') {
            $this->dividenData = $data['CashDividends'];
            return $data;
        }
    }

    private function getTickerNextPayments(array $tickerData): array
    {
        $payments = [];
        //$currentDate = new DateTime();
        $dividends = $tickerData['CashDividends'];
        foreach ($dividends as $dividend) {
            $payDate = new DateTime($dividend['PayDate']);
            //if ($payDate > $currentDate) {
            $payments[] = $dividend;
            //}
        }
        return $payments;
    }

    /**
     * Undocumented function
     *
     * @param string $ticker
     * @return void
     */
    public function getData(string $ticker): ?array
    {
        $tickerNextPayments = [];
        $symbol = $ticker;
        if (isset($this->translate[$ticker])) {
            $symbol = $this->translate[$ticker];
        }

        // Cache it
        // The callable will only be executed on a cache miss.
        $pool = new FilesystemAdapter();
        $token = $pool->get('seeking_alpha_token', function (ItemInterface $item): array {
            $item->expiresAfter(3600);

            // ... do some HTTP request or heavy computations
            $computedValue = $this->getToken();

            return $computedValue;
        });


        //$token = $this->getToken();

        $tickerData = [];
        $tickerData = $this->getStockHistory($token, $symbol);
        if ($tickerData === null || count($tickerData['CashDividends']) === 0) {
            return null;
        }
        $tickerNextPayments = $this->getTickerNextPayments($tickerData);

        return $tickerNextPayments;
    }
}
