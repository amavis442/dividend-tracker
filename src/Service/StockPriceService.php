<?php
namespace App\Service;

use App\Contracts\Service\StockPriceInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class StockPriceService
{
    protected $services;
    protected $linkTickerToService;
    protected $exchangeRateService;
    protected $client;

    public function __construct(ExchangeRateService $exchangeRateService, HttpClientInterface $client)
    {
        $this->services = [];
        $this->linkTickerToService = [];
    
        $this->client = $client;
        $this->exchangeRateService = $exchangeRateService;
    }

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

    public function getDefault(): ?StockPriceInterface
    {
        return $this->services['_default'] ?? null;
    }

    public function addService(StockPriceInterface $service): self
    {
        $serviceClass = get_class($service);
        $this->services[$serviceClass] = $service;

        return $this;
    }

    public function linkServiceToTicker(string $serviceClass, string $symbol): self
    {   
        $this->linkTickerToService[$symbol] = $serviceClass;

        return $this;
    }

    public function getService(string $symbol): StockPriceInterface
    {
        if (isset($this->linkTickerToService[$symbol])) {
            $serviceClass = $this->linkTickerToService[$symbol];
            return $this->services[$serviceClass];
        }
        return $this->services['_default'];
    }

    public function getQuotes(array $symbols, string $cacheTag): array
    {
        $result = $this->getDefault()->getQuotes($symbols, $cacheTag);
    
        return $result;
    }

    public function getMarketPrice(string $symbol):?float
    {
        return $this->getDefault()->getMarketPrice($symbol);
    }
}
