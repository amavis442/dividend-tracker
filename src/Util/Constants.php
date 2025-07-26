<?php

namespace App\Util;


class Constants
{
    public const AMOUNT_PRECISION = 10000000;
    public const VALUTA_PRECISION = 1000;
    public const TAX = 15; // 15%
    public const TAX_GB = 35; // 35%
    public const EXCHANGE = 1.19;
    public const EXCHANGE_USDEUR = 0.84; // 1 USD = 0.84 EUR
    public const EXCHANGE_GBXEUR = 0.011082; // 1 GBX (Penny) = 0.011082 EUR 0.539 GBX = 53.9 x 0.011082 = 0.5973198 EUR
}
