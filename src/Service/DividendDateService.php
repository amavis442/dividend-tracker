<?php

namespace App\Service;

use App\Contracts\Service\DividendDatePluginInterface;
use RuntimeException;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class DividendDateService
{
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
    private $services;

    /**
     * Which service is linked to ticker
     *
     * @var array
     */
    private $linkToService;

    public function __construct(HttpClientInterface $client)
    {
        $this->client = $client;
        $this->services = [];
        $this->linkToService = [];
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
        if (!$service instanceof DividendDatePluginInterface) {
            throw new \RuntimeException("Class [" . $serviceClass . "] should implement StockPriceInterface");
        }

        $this->services[$serviceClass] = $service;
        if ($symbols) {
            foreach ($symbols as $symbol) {
                $this->linkServiceToTicker($serviceClass, $symbol);
            }
        }

        return $this;
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
        if (!$service instanceof DividendDatePluginInterface) {
            throw new \RuntimeException("Class [" . $serviceClass . "] should implement DividendDatePluginInterface");
        }
        $this->services['_default'] = $this->services[$serviceClass];

        return $this;
    }

    /**
     * Fallback service
     *
     * @return DividendDatePluginInterface|null
     */
    public function getDefault(): ?DividendDatePluginInterface
    {
        return $this->services['_default'] ?? null;
    }

    /**
     * Explicit link between symbol and service
     *
     * @param string $serviceClass
     * @param string $symbol
     * @return self
     */
    private function linkServiceToTicker(string $serviceClass, string $symbol): self
    {
        if (!isset($this->services[$serviceClass])) {
            throw new RuntimeException('Use addService first before linking to ticker symbol: ' . $symbol);
        }

        $this->linkToService[$symbol] = $serviceClass;

        return $this;
    }

    /**
     * Get a service which is explitly linked
     *
     * @param string $symbol
     * @return StockPriceInterface
     */
    public function getService(string $symbol): ?DividendDatePluginInterface
    {
        if (isset($this->linkToService[$symbol])) {
            $serviceClass = $this->linkToService[$symbol];
            return $this->services[$serviceClass];
        }

        return $this->services['_default'] ?? null;
    }

    /**
     * Return the parsed dividend data
     *
     * @param string $symbol
     * @return array|null
     */
    public function getData(string $symbol): ?array
    {
        $service = $this->getService($symbol);
        if (isset($service)) {
            return $service->getData($symbol);
        }
        return [];
    }
}
