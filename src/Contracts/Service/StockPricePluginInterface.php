<?php

namespace App\Contracts\Service;

interface StockPricePluginInterface
{

    /**
     * Get the marketprice of the symbols
     *
     * @param array $symbols
     * @return array|null
     */
    public function getQuotes(array $symbols): ?array;
}
