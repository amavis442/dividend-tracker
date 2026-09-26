<?php

namespace App\Decorator;

use App\Repository\TickerRepository;
use App\Entity\Tax;

final class TickerTaxDecorator
{


    /**
     * Decorates array $tickers with \App\Entity\Tax
     * @param array &$tickers
     */
    public function decorate(array &$tickers, array $taxes)
    {
        foreach ($taxes as $id => $t) {
            $tickers[$id]['tax'] = $t;
        }
    }
}
