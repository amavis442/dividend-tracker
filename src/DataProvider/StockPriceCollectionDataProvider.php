<?php

// api/src/DataProvider/StockPriceCollectionDataProvider.php

namespace App\DataProvider;

use App\Entity\StockPrice;
use App\Repository\TickerRepository;
use App\Service\StockPriceService;

final class StockPriceCollectionDataProvider
{
    private $tickerRepository;
    private $stockPriceService;

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
        $this->stockPriceService->getQuotes($symbols);
        $data = [];
        foreach ($symbols as $symbol) {
            $marketPrice = $this->stockPriceService->getMarketPrice($symbol);
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
