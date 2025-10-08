<?php

namespace App\Service\DividendDate;

use App\Contracts\Service\DividendDatePluginInterface;
use RuntimeException;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * This is the factory for gtting dividend date data from different sources like ishares or seekingalpha.
 * The implementation to get the specific data can be found in App\Service\DividendDate\[name of service]
 */
class DividendDateService
{
	/**
	 * Http client
	 *
	 * @var HttpClientInterface
	 */
	private HttpClientInterface $client;

	/**
	 * Initialized services
	 *
	 * @var array
	 */
	private array $services;
	private array $fallbackServices;

	/**
	 * Which service is linked to ticker
	 *
	 * @var array
	 */
	private array $linkToService;

	/*
	private $userAgents = [
		'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.6 Safari/605.1.1	31.48',
		'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.3	24.07',
		'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_12_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/103.0.0.0 Safari/537.3	17.59',
		'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:133.0) Gecko/20100101 Firefox/133.	7.41',
		'Mozilla/5.0 (Windows NT 6.1; Win64; x64; rv:109.0) Gecko/20100101 Firefox/115.	4.63',
		'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36 Edg/131.0.0.	3.7',
		'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36 Herring/97.1.8280.8	2.78',
		'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.3	1.85',
		'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Safari/537.36 OPR/115.0.0.	1.85',
		'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36 AtContent/95.5.5462.5	0.93',
		'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/70.0.3538.102 Safari/537.36 Edge/18.1958	0.93',
		'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/109.0.0.0 Safari/537.3	0.93',
		'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36 OPR/114.0.0.	0.93',
		'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.3	0.93',
	];
	*/

	public function __construct(HttpClientInterface $client)
	{
		//$client->headers['User-Agent'] = $this->userAgents[array_rand($this->userAgents)];
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
	public function addService(
		string $serviceClass,
		?array $symbols = [],
		?string $api_key = '',
		bool $isFallBack = false
	): self {
		$service = new $serviceClass($this->client);
		if ($api_key) {
			$service->setApiKey($api_key);
		}

		if (!$service instanceof DividendDatePluginInterface) {
			throw new \RuntimeException(
				'Class [' .
					$serviceClass .
					'] should implement DividendDatePluginInterface'
			);
		}

		$this->services[$serviceClass] = $service;
		if ($symbols) {
			foreach ($symbols as $symbol) {
				$this->linkServiceToTicker($serviceClass, $symbol);
			}
		}

		if ($isFallBack) {
			$this->fallbackServices[] = $service;
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
			throw new \RuntimeException(
				'Add service first with addService() then call setDefault. Class does not exist [' .
					$serviceClass .
					']'
			);
		}
		$service = $this->services[$serviceClass];
		if (!$service instanceof DividendDatePluginInterface) {
			throw new \RuntimeException(
				'Class [' .
					$serviceClass .
					'] should implement DividendDatePluginInterface'
			);
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
	private function linkServiceToTicker(
		string $serviceClass,
		string $symbol
	): self {
		if (!isset($this->services[$serviceClass])) {
			throw new RuntimeException(
				'Use addService first before linking to ticker symbol: ' .
					$symbol
			);
		}

		$this->linkToService[$symbol] = $serviceClass;

		return $this;
	}

	/**
	 * Get a service which is explitly linked
	 *
	 * @param string $symbol
	 * @return null|DividendDatePluginInterface
	 */
	public function getService(string $symbol): ?DividendDatePluginInterface
	{
		if (isset($this->linkToService[$symbol])) {
			$serviceClass = $this->linkToService[$symbol];
			return $this->services[$serviceClass];
		}

		return $this->services['_default'] ?: null;
	}

	/**
	 * Return the parsed dividend data
	 *
	 * @param string $symbol
	 * @return array|null
	 */
	public function getData(string $symbol, string $isin): ?array
	{
		$service = $this->getService($symbol);
		if (isset($service)) {
			$result = $service->getData($symbol, $isin);
			if ($result && count($result) > 0) {
				return $result;
			}
		}
		foreach ($this->fallbackServices as $service) {
			// These should have cached data, so they play nice with free api's
			return $service->getData($symbol, $isin);
		}

		return [];
	}
}
