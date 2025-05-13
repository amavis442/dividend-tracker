<?php

namespace App\Service\DividendDate;

use App\Contracts\Service\DividendDatePluginInterface;

/**
 * Make sure the dividend data is null for different exchanges that use the same ticker symbol
 * but have nothing in common.
 */
class NullService extends AbstractDividendDate implements DividendDatePluginInterface
{
    public function getData(string $symbol, string $isin): ?array
    {
        return null;
    }
}
