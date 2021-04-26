<?php

namespace App\Contracts\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

interface DividendRetrievalInterface
{
    public function getLatest(string $ticker): ?array;
    public function setClient(HttpClientInterface $client);
}
