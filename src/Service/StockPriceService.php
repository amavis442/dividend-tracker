<?php
namespace App\Service;

use App\Contracts\Service\StockPriceInterface;
use RuntimeException;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class StockPriceService
{
    /**
     * Available service
     *
     * @var array
     */
    protected $services;
    /**
     * Explicit symbol to service links
     *
     * @var array
     */
    protected $linkTickerToService;
    /**
     * Get the current exchange rates
     *
     * @var ExchangeRateService
     */
    protected $exchangeRateService;
    /**
     * Get data from external sources
     *
     * @var HttpClientInterface
     */
    protected $client;
    /**
     * Cache the data, else it will be very slow
     *
     * @var CacheInterface
     */
    protected $stockCache;
    /**
     * Timestamp
     *
     * @var integer
     */
    private $cacheTimeStamp;

    /**
     *
     * @param ExchangeRateService $exchangeRateService
     * @param CacheInterface $stockCache
     * @param HttpClientInterface $client
     */
    public function __construct(ExchangeRateService $exchangeRateService, CacheInterface $stockCache, HttpClientInterface $client)
    {
        $this->services = [];
        $this->linkTickerToService = [];

        $this->client = $client;
        $this->exchangeRateService = $exchangeRateService;
        $this->stockCache = $stockCache;
    }

    /**
     * Fallback service to use
     *
     * @param string $serviceClass
     * @return self
     */
    public function setDefault(string $serviceClass): self
    {
        if (!isset($this->services[$serviceClass])) {
            throw new \RuntimeException("Add service first with addService() then call setDefault. Class does not exist [" . $serviceClass . "]");
        }
        $service = $this->services[$serviceClass];
        if (!$service instanceof StockPriceInterface) {
            throw new \RuntimeException("Class [" . $serviceClass . "] should implement StockPriceInterface");
        }
        $this->services['_default'] = $this->services[$serviceClass];

        return $this;
    }

    /**
     * Fallback service
     *
     * @return StockPriceInterface|null
     */
    public function getDefault(): ?StockPriceInterface
    {
        return $this->services['_default'] ?? null;
    }

    /**
     * Add a service by classname.
     *
     * @param string $serviceClass
     * @return self
     */
    public function addService(string $serviceClass): self
    {
        $service = new $serviceClass($this->client, $this->stockCache, $this->exchangeRateService);
        if (!$service instanceof StockPriceInterface) {
            throw new \RuntimeException("Class [" . $serviceClass . "] should implement StockPriceInterface");
        }

        $this->services[$serviceClass] = $service;

        return $this;
    }

    /**
     * Explicit link between symbol and service
     *
     * @param string $serviceClass
     * @param string $symbol
     * @return self
     */
    public function linkServiceToTicker(string $serviceClass, string $symbol): self
    {
        if (!isset($this->services[$serviceClass])) {
            throw new RuntimeException('Use addService first before linking to ticker symbol: ' . $symbol);
        }

        $this->linkTickerToService[$symbol] = $serviceClass;

        return $this;
    }

    /**
     * Get a service which is explitly linked
     *
     * @param string $symbol
     * @return StockPriceInterface
     */
    public function getService(string $symbol): StockPriceInterface
    {
        if (isset($this->linkTickerToService[$symbol])) {
            $serviceClass = $this->linkTickerToService[$symbol];
            return $this->services[$serviceClass];
        }
        return $this->services['_default'];
    }

    /**
     * Get a batch of stock data
     *
     * @param array $symbols
     * @return array
     */
    public function getQuotes(array $symbols): array
    {
        $result = $this->getDefault()->getQuotes($symbols);
        $this->cacheTimeStamp = $result['timestamp'];

        if (count($this->linkTickerToService) > 0) {
            foreach ($this->linkTickerToService as $symbol => $serviceClass) {
                $service = $this->getService($symbol);
                $service->getMarketPrice($symbol);
            }
        }

        return $result;
    }

    /**
     * Get a price of a stock
     *
     * @param string $symbol
     * @return float|null
     */
    public function getQuote(string $symbol): ?float
    {
        if (isset($this->linkTickerToService[$symbol])) {
            $serviceClass = $this->linkTickerToService[$symbol];
            $service = $this->services[$serviceClass];
            $price = $service->getQuote($symbol);

            return $price;
        }
        return $this->getDefault()->getQuote($symbol);
    }

    /**
     * Get a price of a stock
     *
     * @param string $symbol
     * @return float|null
     */
    public function getMarketPrice(string $symbol): ?float
    {
        if (isset($this->linkTickerToService[$symbol])) {
            $serviceClass = $this->linkTickerToService[$symbol];
            $service = $this->services[$serviceClass];
            $price = $service->getMarketPrice($symbol);

            return $price;
        }
        return $this->getDefault()->getMarketPrice($symbol);
    }

    /**
     * Get cache timestamp of the default service
     *
     * @return  integer
     */
    public function getCacheTimeStamp()
    {
        return $this->cacheTimeStamp;
    }
}
