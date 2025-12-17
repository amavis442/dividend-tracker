<?php

namespace App\DataProvider;

use App\DataProvider\CorporateActionDataProvider;
use App\DataProvider\DividendDataProvider;
use App\Dto\PieDataDto;
use App\Entity\Calendar;
use App\Entity\CorporateAction;
use App\Entity\Pie;
use App\Entity\Position;
use App\Entity\Trading212PieInstrument;
use App\Repository\Trading212PieMetaDataRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use App\Service\ExchangeRate\ExchangeRateInterface;

class PieDataProvider
{

    public function __construct(
        private readonly Trading212PieMetaDataRepository $trading212PieMetaDataRepository,
        private readonly CorporateActionDataProvider $corporateActionDataProvider,
        private readonly ExchangeRateInterface $exchangeRate,
        private readonly DividendDataProvider $dividendDataProvider,
    ) {}

    /**
     * Returns an object that contains:
     *   - metaData (Collection)
     *   - instruments (Collection)
     *   - corporateActions (Collection)
     *   - dividends (Collection)
     *
     * @param Pie $pie
     *
     * @return PieDataDto
     */
    public function load(
        Pie $pie
    ): PieDataDto {

        $metaDatas = new ArrayCollection($this->trading212PieMetaDataRepository->findBy(
            ['pie' => $pie],
            ['createdAt' => 'DESC']
        ));
        // Get the most current one
        $metaData = $metaDatas->first();

        if (!$metaData) {
            throw new NotFoundHttpException('No metadata found for this pie.');
        }

        /**
         * Instruments in a pie reported by Trading212
         *
         * @var Collection<int, Trading212PieInstrument> $instruments
         */
        $instruments = $metaData->getTrading212PieInstruments();

        /**
         * Only need positions with instruments who have positions
         *
         * @var Collection<int, Position> $positions
         */
        $positions = $instruments->map(fn($i) => $i->getPosition())
            ->filter(fn(?Position $p) => null !== $p);

        if ($positions->isEmpty()) {
            throw new NotFoundHttpException('No positions found for this pie.');
        }

        /**
         * From those positions we only need the ticker Id
         *
         * @var Collection<int, int> $tickerIds
         */
        $tickerIds = $positions->map(fn($p) => $p->getTicker());

        if ($tickerIds->isEmpty()) {
            throw new NotFoundHttpException('No tickers found for this pie.');
        }

        /**
         * Get the corporate actions per ticker
         *
         * @var Collection<int, CorporateAction> $corporateActions
         */
        $corporateActions = new ArrayCollection($this->corporateActionDataProvider->load($tickerIds->toArray()));

        /**
         * Get the dividend Calendars per ticker
         *
         * @var Collection<int, array<int, Calendar>> $dividends
         */
        $dividends = new ArrayCollection($this->dividendDataProvider->load($tickerIds->toArray()));

        return new PieDataDto(
            metaData: $metaDatas,
            instruments: $instruments,
            tickerIds: $tickerIds,
            positions: $positions,
            corporateActions: $corporateActions,
            dividends: $dividends
        );
    }


    /**
     * Return an array of exchange rates.
     *
     * @return array
     */
    public function getExchangeRates():array {
        return $this->exchangeRate->getRates();
    }
}
