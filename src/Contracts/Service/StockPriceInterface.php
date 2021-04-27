<?php

namespace App\Contracts\Service;

interface StockPriceInterface
{

    /**
     * Get the marketprice
     *
     * @param array $symbols
     * @return array|null
     */
    public function getQuotes(array $symbols): ?array;

    /**
     * Get marketprice for symbol
     *
     * @param string $symbol
     * @return null|float
     */
    public function getMarketPrice(string $symbol): ?float;

    /**
     * Get market quote for 1 ticker
     *
     * @param string $symbol
     * @return float|null
     */
    public function getQuote(string $symbol): ?float;
}
