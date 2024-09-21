<?php

namespace App\Traits;

use App\Entity\Pie;
use App\Entity\Ticker;
use App\Entity\TickerAutocomplete;
use App\Entity\PieSelect;
use App\Entity\SearchForm;
use App\Form\SearchFormType;
use App\Form\TickerAutocompleteType;
use App\Form\PieSelectFormType;
use App\Repository\PieRepository;
use App\Repository\TickerRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Form;

trait TickerAutocompleteTrait
{
    /**
     * $all means also the tickers who have no active positions.
     * @return array{Form, Ticker}
     */
    protected function searchTicker(
        Request $request,
        string $cacheKey,
        bool $include_all_tickers = false
    ): array {
        $ticker = new Ticker();

        if ($request->hasSession()) {
            $tickerCache = $request->getSession()->get($cacheKey, null);
            if ($tickerCache instanceof Ticker) {
                $ticker = $tickerCache;
            }
        }

        $tickerAutoComplete = new TickerAutocomplete();
        /**
         * @var Form $form
         */
        $form = $this->createForm(
            TickerAutocompleteType::class,
            $tickerAutoComplete,
            ["extra_options" => ["include_all_tickers" => $include_all_tickers]]
        );
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $request->getSession()->set($cacheKey, null);

            $ticker = $tickerAutoComplete->getTicker();
            $request->getSession()->set($cacheKey, $ticker);
        }
        return [$form, $ticker];
    }

    protected function selectPie(Request $request, string $cacheKey)
    {
        $pie = new Pie();
        $pieSelect = new PieSelect();
        if ($request->hasSession()) {
            $pieSelect = $request->getSession()->get($cacheKey, null);
            $pie = $pieSelect->getPie();
        }

        $pieSelectForm = $this->createForm(
            PieSelectFormType::class,
            $pieSelect
        );
        $pieSelectForm->handleRequest($request);

        if ($pieSelectForm->isSubmitted() && $pieSelectForm->isValid()) {
            $pieSelect = $pieSelectForm->getData();
            $request->getSession()->set($cacheKey, $pieSelect);
            $pie = $pieSelect->getPie();
        }

        return [$pieSelectForm, $pie];
    }

    protected function searchTickerAndPie(
        Request $request,
        TickerRepository $tickerRepository,
        PieRepository $pieRepository,
        string $cacheKey,
        bool $include_all_tickers = false
    ) {
        $pie = new Pie();
        $ticker = new Ticker();

        $searchFormData = new SearchForm();

        $request->getSession()->set($cacheKey, [0 => null, 1 => null]);

        if ($request->hasSession()) {
            [$tickerId, $pieId] = $request
                ->getSession()
                ->get($cacheKey, [0 => null, 1 => null]);

            // If you just store searchFormData in cache, symfony will complain about
            // App\Entity\Ticker not managed by doctrine
            if ($tickerId != null) {
                $ticker = $tickerRepository->find($tickerId);
                $searchFormData->setTicker($ticker);
            }
            if ($pieId != null) {
                $pie = $pieRepository->find($pieId);
                $searchFormData->setPie($pie);
            }
        }

        $searchForm = $this->createForm(
            SearchFormType::class,
            $searchFormData,
            ["extra_options" => ["include_all_tickers" => $include_all_tickers]]
        );
        $searchForm->handleRequest($request);

        if ($searchForm->isSubmitted() && $searchForm->isValid()) {
            $searchFormData = $searchForm->getData();
            $pie = $searchFormData->getPie();
            $ticker = $searchFormData->getTicker();
            $request
                ->getSession()
                ->set($cacheKey, [
                    $ticker ? $ticker->getId() : null,
                    $pie ? $pie->getId() : null,
                ]);
        }

        return [$searchForm, $ticker, $pie];
    }
}
