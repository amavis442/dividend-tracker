<?php
namespace App\Service;

use App\Contracts\Service\StockPricePluginInterface;
use RuntimeException;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
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
     * Holder
     *
     * @var array
     */
    private $data;

    /**
     *
     * @param ExchangeRateService $exchangeRateService
     * @param CacheInterface $stockCache
     * @param HttpClientInterface $client
     */
    public function __construct(
        ExchangeRateService $exchangeRateService,
        CacheInterface $stockCache,
        HttpClientInterface $client
    ) {
        $this->services = [];
        $this->linkTickerToService = [];

        $this->client = $client;
        $this->exchangeRateService = $exchangeRateService;
        $this->stockCache = $stockCache;
        $this->data = [];
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
        if (!$service instanceof StockPricePluginInterface) {
            throw new \RuntimeException("Class [" . $serviceClass . "] should implement StockPricePluginInterface");
        }
        $this->services['_default'] = $this->services[$serviceClass];

        return $this;
    }

    /**
     * Fallback service
     *
     * @return StockPricePluginInterface|null
     */
    public function getDefault(): ?StockPricePluginInterface
    {
        return $this->services['_default'] ?? null;
    }

    /**
     * Add a service by classname.
     *
     * @param string $serviceClass
     * @return self
     */
    public function addService(string $serviceClass, ?array $symbols = []): self
    {
        $service = new $serviceClass($this->client);
        if (!$service instanceof StockPricePluginInterface) {
            throw new \RuntimeException("Class [" . $serviceClass . "] should implement StockPriceInterface");
        }

        $this->services[$serviceClass] = $service;
        if ($symbols) {
            foreach ($symbols as $symbol) {
                $this->linkServiceToTicker[$symbol] = $serviceClass;
            }
        }
        
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
    public function getService(string $symbol): StockPricePluginInterface
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
        $services = $this->services;
        $data = $this->stockCache->get('stockprices', function (ItemInterface $item) use ($services, $symbols) {
            $item->expiresAfter(300);
            $result = [];
            if (count($services) > 0) {
                $defaultService = $services["_default"];
                $result = $defaultService->getQuotes($symbols);
                foreach ($services as $serviceClass => $service) {
                    if ($serviceClass !== '_default' && $service !== $defaultService) {
                        $result = array_merge($result, $service->getQuotes($symbols));
                    }
                }
            }
            $result['timestamp'] = time();

            return $result;
        });

        $this->cacheTimeStamp = $data['timestamp'];
        $this->data = $data;

        return $data;
    }

    /**
     * Get a price of a stock
     *
     * @param string $symbol
     * @return float|null
     */
    public function getQuote(string $symbol): ?float
    {
        if (isset($this->data[$symbol])) {
            $rates = $this->exchangeRateService->getRates();
            $data = $this->data[$symbol];
            if (!isset($data['regularMarketPrice'])) {
                return null;
            }
            $price = $data['regularMarketPrice'];
            $currency = $data['currency'];

            return $price / ($rates[$currency]);
        }

        return null;
    }

    /**
     * Get a price of a stock
     *
     * @param string $symbol
     * @return float|null
     */
    public function getMarketPrice(string $symbol): ?float
    {
        return $this->getQuote($symbol);
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
