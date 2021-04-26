<?php
namespace App\Service;

use DateTime;
use RuntimeException;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use App\Contracts\Service\DividendRetrievalInterface;

class DividendDateService
{
    public const TOKEN_URL = "https://seekingalpha.com/market_data/xignite_token";
    public const URL = "https://globalhistorical.xignite.com/";
    /**
     * Hold sthe returned dividend data
     *
     * @var array
     */
    private $dividenData;

    /**
     * Http client
     *
     * @var HttpClientInterface
     */
    private $client;

    /**
     * Initialized services
     *
     * @var Array
     */
    private $externalServices;

    /**
     * Which service is linked to ticker
     *
     * @var array
     */
    private $tickerLinkedToService;

    public function __construct(HttpClientInterface $client, ISharesService $iSharesService)
    {
        $this->client = $client;
        $this->iSharesService = $iSharesService;
        $this->externalServices = [];
        $this->tickerLinkedToService = [];
    }

    public function addExternalService(string $ticker, string $serviceClass)
    {
        if (!isset($this->externalServices[$serviceClass])) {
            $service = new $serviceClass();
            if ($service instanceof DividendRetrievalInterface) {
                $service->setClient($this->client);
                $this->externalServices[$serviceClass] = $service;
                
            }
        }
        if (isset($this->externalServices[$serviceClass])) {
            $this->tickerLinkedToService[$ticker] = $serviceClass;
        }
    }

    public function getToken()
    {
        $response = $this->client->request('GET', self::TOKEN_URL);
        if ($response->getStatusCode() !== 200) {
            throw new RuntimeException('Can not get token...');
        }

        return $response->toArray();
    }

    public function getStockHistory(array $token, string $ticker)
    {
        $url = "https://globalhistorical.xignite.com/v3/xGlobalHistorical.json/GetCashDividendHistory?"
            . "IdentifierType=Symbol&Identifier=" . $ticker
            . "&StartDate=01/01/" . (date('Y') - 3) . "&EndDate=12/30/" . (date('Y') + 3) . "&"
            . "IdentifierAsOfDate=&CorporateActionsAdjusted=true&_token="
            . $token['_token'] . "&_token_userid=" . $token['_token_userid'];

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
        $currentDate = new DateTime();
        $dividends = $tickerData['CashDividends'];
        foreach ($dividends as $dividend) {
            $payDate = new DateTime($dividend['PayDate']);
            if ($payDate > $currentDate) {
                $payments[] = $dividend;
            }
        }
        return $payments;
    }

    /**
     * Undocumented function
     *
     * @param string $ticker
     * @return void
     */
    public function getUpcommingDividendInfo(string $ticker): ?array
    {
        $tickerNextPayments = [];

        if (isset($this->tickerLinkedToService[$ticker])) {
            $serviceClass = $this->tickerLinkedToService[$ticker];
            $tickerData = [];
            $tickerData['CashDividends'][] = $this->externalServices[$serviceClass]->getLatest($ticker);
        } else {
            $token = $this->getToken();
            $tickerData = [];
            $tickerData = $this->getStockHistory($token, $ticker);
            if ($tickerData === null || count($tickerData['CashDividends']) === 0) {
                return null;
            }
        }
        $tickerNextPayments = $this->getTickerNextPayments($tickerData);

        return $tickerNextPayments;
    }
}
