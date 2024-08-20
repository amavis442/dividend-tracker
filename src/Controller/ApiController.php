<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use App\DataProvider\StockPriceCollectionDataProvider;

#[Route('/api')]
class ApiController extends AbstractController
{
  #[Route('/stock_prices/{stock}', name: 'api_stockprice')]
  public function getStockPrice(StockPriceCollectionDataProvider $dataProvider, Request $request, string $stock): JsonResponse
  {
    $data = $dataProvider->getCollection();
    $price = 0.0;
    foreach ($data as $item) {
      if ($item->getSymbol() == $stock) {
        $price = $item->getPrice();
        break;
      }
    }

    $response = new JsonResponse(['result' => 'ok', 'symbol' => $stock, 'price' => $price]);
    return $response;
  }

  #[Route('/prices', name: 'api_prices')]
  public function getPrices(StockPriceCollectionDataProvider $dataProvider, Request $request): JsonResponse
  {
    //Yahoo services do not work anymore. So no live prices :(

    /*
    $data = $dataProvider->getCollection();
    $price = 0.0;
    $output = [];
    foreach ($data as $item) {
      $output[] = ['symbol' => $item->getSymbol(), 'price' => $item->getPrice()];
    }
    */

    $response = new JsonResponse(['result' => 'ok', 'data' => []]);
    return $response;
  }
}
