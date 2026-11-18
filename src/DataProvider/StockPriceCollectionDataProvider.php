<?php

// api/src/DataProvider/StockPriceCollectionDataProvider.php

namespace App\DataProvider;

use App\Dto\StockPrice;
use App\Exception\ExchangeRateUnavailableException;
use App\Repository\TickerRepository;
use App\Service\StockPriceService;

final class StockPriceCollectionDataProvider
{
    private TickerRepository $tickerRepository;
    private StockPriceService $stockPriceService;

    public function __construct(TickerRepository $tickerRepository, StockPriceService $stockPriceService)
    {
        $this->stockPriceService = $stockPriceService;
        $this->tickerRepository = $tickerRepository;
    }

    public function getCollection(): array
    {
        $tickers = $this->tickerRepository->getActive();
        foreach ($tickers as $ticker) {
            $symbol = $ticker->getSymbol();
            $symbols[] = $symbol;
        }
        if (!isset($symbols)) {
            return [];
        }

        $this->stockPriceService->getQuotes($symbols);

        $data = [];
        foreach ($symbols as $symbol) {
            try {
                $marketPrice = $this->stockPriceService->getMarketPrice($symbol);
            } catch (ExchangeRateUnavailableException $exception) {
                if ($exception->providerFailed) {
                    throw $exception;
                }

                // One price without a rate should not hide the others.
                $marketPrice = null;
            }
            $stockprice = new StockPrice();
            $stockprice->setSymbol($symbol);
            $stockprice->setPrice(null);
            if ($marketPrice) {
                $stockprice->setPrice($marketPrice);
            }
            $data[] = $stockprice;
        }
        return $data;
    }
}
