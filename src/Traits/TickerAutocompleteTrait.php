<?php

namespace App\Traits;

use App\Entity\TickerAutocomplete;
use App\Form\TickerAutocompleteType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Form;

trait TickerAutocompleteTrait
{

    /**
     * $all means also the tickers who have no active positions.
     */
    protected function searchTicker(Request $request, string $searchKey, bool $include_all_tickers = false): array
    {
        if ($request->hasSession()) {
            $searchCriteria = $request->getSession()->get($searchKey, '');
        }

        $searchTicker = new TickerAutocomplete();
        $searchCriteria = '';
        /**
         * @var Form $form
         */
        $form = $this->createForm(TickerAutocompleteType::class, $searchTicker, ['extra_options' => ['include_all_tickers' => $include_all_tickers]]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $request->getSession()->set($searchKey, '');

            if ($searchTicker->getTicker()) {
                $searchCriteria = $searchTicker->getTicker()->getIsin();
                $request->getSession()->set($searchKey, $searchCriteria);
            }
        }
        if ($searchCriteria == null) {
            $searchCriteria = '';
        }

        return [$form, $searchCriteria];
    }
}
