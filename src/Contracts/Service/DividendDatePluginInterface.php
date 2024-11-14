<?php

namespace App\Contracts\Service;

interface DividendDatePluginInterface
{
    public function getData(string $symbol, string $isin): ?array;
    public function setApiKey(?string $api_key): void;
}
