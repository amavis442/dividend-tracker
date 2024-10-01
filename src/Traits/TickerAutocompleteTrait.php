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
use App\Repository\TickerRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Response;

trait TickerAutocompleteTrait
{
    protected function searchTicker(Request $request, string $cacheKey, TickerRepository $tickerRepository): array
    {
        $tickerAutoComplete = new TickerAutocomplete();
        $ticker = null;

        $tickerAutoCompleteCache = $request
            ->getSession()
            ->get($cacheKey, null);

        if ($tickerAutoCompleteCache instanceof TickerAutocomplete) {
            // We need a mapped entity else symfony will complain
            // This works, but i do not know if it is the best solution
            if (
                $tickerAutoCompleteCache->getTicker() &&
                $tickerAutoCompleteCache->getTicker()->getId()
            ) {
                $ticker = $tickerRepository->find(
                    $tickerAutoCompleteCache->getTicker()->getId()
                );
                $tickerAutoComplete->setTicker($ticker);
            }
        }

        /**
         * @var Form $form
         */
        $form = $this->createForm(
            TickerAutocompleteType::class,
            $tickerAutoComplete,
            ['extra_options' => ['include_all_tickers' => true]]
        );
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $ticker = $tickerAutoComplete->getTicker();
            $request->getSession()->set($cacheKey, $tickerAutoComplete);
        }

        return [$form, $ticker];
    }


    /**
     * @return (\App\Entity\Pie|\Symfony\Component\Form\FormInterface|mixed)[]
     *
     * @psalm-return list{\Symfony\Component\Form\FormInterface, \App\Entity\Pie|mixed}
     */
    protected function selectPie(Request $request, string $cacheKey): array
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
        string $cacheKey,
        bool $include_all_tickers = false
    ): array {
        $pie = null;
        $ticker = null;

        $searchFormData = new SearchForm();
        $sessionFormData = $request->getSession()->get($cacheKey, null);

        if ($sessionFormData instanceof SearchForm) {
            if ($sessionFormData->getPie() instanceof Pie) {
                $pie = $sessionFormData->getPie();
                $searchFormData->setPie($pie);
            }

            if (
                $sessionFormData->getTicker() &&
                $sessionFormData->getTicker()->getId()
            ) {
                $ticker_id = $sessionFormData->getTicker()->getId();
                $ticker = $tickerRepository->find($ticker_id);
                $searchFormData->setTicker($ticker);
            }
        }

        $searchForm = $this->createForm(
            SearchFormType::class,
            $searchFormData,
            ['extra_options' => ['include_all_tickers' => $include_all_tickers]]
        );
        $searchForm->handleRequest($request);

        if ($searchForm->isSubmitted() && $searchForm->isValid()) {
            $pie = $searchFormData->getPie();
            $ticker = $searchFormData->getTicker();
            $request->getSession()->set($cacheKey, $searchFormData);
        }

        return [$searchForm, $ticker, $pie];
    }
}
