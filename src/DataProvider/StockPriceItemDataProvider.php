<?php

// api/src/DataProvider/StockPriceCollectionDataProvider.php

namespace App\DataProvider;

use ApiPlatform\Core\DataProvider\ItemDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use App\Entity\StockPrice;
use App\Repository\TickerRepository;
use App\Service\DividendService;
use App\Service\StockPriceService;

final class StockPriceItemDataProvider implements ItemDataProviderInterface, RestrictedDataProviderInterface
{
    private $tickerRepository;
    private $stockPriceService;
    
    public function __construct(TickerRepository $tickerRepository, StockPriceService $stockPriceService)
    {
        $this->stockPriceService = $stockPriceService;
        $this->tickerRepository = $tickerRepository;
    }

    public function supports(string $resourceClass, string $operationName = null, array $context = []): bool
    {
        return StockPrice::class === $resourceClass;
    }

    public function getItem(string $resourceClass, $id, string $operationName = null, array $context = []): ?StockPrice
    {
        $tickers = $this->tickerRepository->getActive();
        foreach ($tickers as $ticker) {
            $symbol = $ticker->getSymbol();
            $symbols[] = $symbol;
        }
        $this->stockPriceService->getQuotes($symbols);

        $marketPrice = $this->stockPriceService->getMarketPrice($id);

        $stockprice = new StockPrice();
        $stockprice->setSymbol($id);
        $stockprice->setPrice(round($marketPrice, 2));

        return $stockprice;
    }
}
