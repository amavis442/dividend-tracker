<?php

namespace App\Service\Contracts;

use Symfony\Contracts\HttpClient\HttpClientInterface;

interface DividendRetrievalInterface
{
    public function getLatest(string $ticker): ?array;
    public function setClient(HttpClientInterface $client);
}
