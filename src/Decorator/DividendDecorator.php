<?php

namespace App\Decorator;

use App\Decorator\Factory\AdjustedDividendDecoratorFactory;
use Doctrine\Common\Collections\Collection;

final class DividendDecorator
{
    public function __construct(private readonly AdjustedDividendDecoratorFactory $factory) {}

    /**
     */
    public function decorate(array &$tickers, Collection $dividends, Collection $corporateActions)
    {
        // The original logic that called the factory
        $this->factory->load($dividends->toArray(), $corporateActions->toArray());

        $result = [];
        foreach ($dividends as $tickerId => $dividendData) {
            foreach ($dividendData as $dividend) {
                $decorator = $this->factory->decorate($dividend->getTicker());
                $result[$tickerId] = $decorator->getAdjustedDividend();
            }
        }

        foreach ($result as $tickerId => $ad) {
            $tickers[$tickerId]['adjustedDividend'] = $ad;
        }
    }
}
