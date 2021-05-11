<?php

namespace App\Contracts\Service;

interface DividendDatePluginInterface
{
    public function getData(string $ticker): ?array;
}
