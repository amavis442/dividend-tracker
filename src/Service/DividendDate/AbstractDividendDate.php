<?php

namespace App\Service\DividendDate;

use Symfony\Contracts\HttpClient\HttpClientInterface;

abstract class AbstractDividendDate
{
    /**
	 * Http client
	 *
	 * @var HttpClientInterface
	 */
	protected HttpClientInterface $client;
	protected string $apiKey = '';

    public const URL = '';

    public function __construct(HttpClientInterface $client)
	{
		$this->client = $client;
	}

   	public function setApiKey(?string $apiKey): void
	{

		$this->apiKey = $apiKey;
	}

	public function getUrl(string $symbol): string
	{
		$url = '';
		$url = str_replace('[SYMBOL]', $symbol, static::URL);
		$url = str_replace('[API_KEY]', (string) $this->apiKey, $url);

		return $url;
	}
}
