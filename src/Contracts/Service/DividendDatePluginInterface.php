<?php

namespace App\Contracts\Service;

interface DividendDatePluginInterface
{
    public function getData(string $symbol): ?array;
    public function setApiKey(?string $api_key): void;
}
