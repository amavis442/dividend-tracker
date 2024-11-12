<?php

namespace App\Service\DividendDate;

use App\Contracts\Service\DividendDatePluginInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Make sure the dividend data is null for different exchanges that use the same ticker symbol
 * but have nothing in common.
 */
class NullService implements DividendDatePluginInterface
{
    /**
     * Http client
     *
     * @var HttpClientInterface
     */
    protected $client;

    public function __construct(HttpClientInterface $client)
    {
        $this->client = $client;
    }

    public function setApiKey(?string $api_key): void
    {

    }
    public function getData(string $symbol): ?array
    {
        return null;
    }
}
