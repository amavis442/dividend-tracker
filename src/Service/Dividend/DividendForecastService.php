<?php

namespace App\Service\Dividend;

use App\Entity\Calendar;
use App\Repository\Trading212PieInstrumentRepository;
use App\Repository\DividendCalendarRepository;
use App\Service\ExchangeRate\ExchangeAndTaxResolver;
use Doctrine\Common\Collections\ArrayCollection;
use App\Decorator\Factory\AdjustedDividendDecoratorFactory;
use App\DataProvider\DividendDataProvider;
use App\DataProvider\CorporateActionDataProvider;

class DividendForecastService
{
	public function __construct(
		protected Trading212PieInstrumentRepository $holdingsSnapshotInstrumentRepository,
		protected DividendCalendarRepository $dividendCalendarRepository,
		protected ExchangeAndTaxResolver $exchangeAndTaxResolver,
		protected DividendAdjuster $dividendAdjuster,
		protected AdjustedDividendDecoratorFactory $adjustedDividendDecoratorFactory,
		protected DividendDataProvider $dividendDataProvider,
		protected CorporateActionDataProvider $corporateActionDataProvider
	) {
	}

	/**
	 * Calculates projected dividend payouts for all instrument snapshots on a given date.
	 *
	 * This method queries snapshot holdings, matches them with calendar entries whose
	 * ex-dividend dates are in the future (relative to snapshot creation), and computes
	 * payout values after tax withholding and currency exchange adjustments.
	 *
	 * @param \DateTime $snapshotDate The date used to retrieve snapshot records.
	 *
	 * @return array Array of projected payouts, each including ticker, payout amount,
	 *               payment and ex-dividend dates, tax withheld, exchange rate, and currency symbol.
	 */
	public function calculateProjectedPayouts(\DateTime $snapshotDate): array
	{
		$snapshots = $this->holdingsSnapshotInstrumentRepository->getSnapshotsByDate(
			$snapshotDate
		);
		$payouts = [];

		$tickers = array_map(function ($snapshot) {
			return $snapshot->getTicker();
		}, $snapshots);

		$dividends = $this->dividendDataProvider->load(
			tickers: $tickers,
			afterDate: $snapshotDate,
			types: [Calendar::REGULAR, Calendar::SPECIAL]
		);
		$actions = $this->corporateActionDataProvider->load($tickers);

		$this->adjustedDividendDecoratorFactory->load(
			dividends: $dividends,
			actions: $actions
		);

		/**
		 * @var \App\Entity\Trading212PieInstrument $snapshot
		 */
		foreach ($snapshots as $snapshot) {
			/**
			 * @var \App\Entity\Ticker $ticker
			 */
			$ticker = $snapshot->getTicker();
			if (!$ticker) {
				continue;
			}

			$dividendDecorator = $this->adjustedDividendDecoratorFactory->decorate(
				$ticker
			);

			$adjustedDividends = $dividendDecorator->getAdjustedDividend();

			/*$calendarEntries = $this->dividendCalendarRepository->getEntriesByTickerAndPayoutDate(
				$ticker,
				$snapshotDate
			);*/

			foreach ($adjustedDividends as $calendarId => $calendarItem) {
				$calendarEntry = $calendarItem['calendar'];

				$exchangeTaxDto = $this->exchangeAndTaxResolver->resolve(
					$ticker,
					$calendarEntry
				);
				$taxRate = $exchangeTaxDto->taxAmount;
				$exchangeRate = $exchangeTaxDto->exchangeRate;

				if (
					$snapshot->getCreatedAt() < $calendarEntry->getPaymentDate()
				) {
					$adjustedCashAmount = $calendarItem['adjusted'];

					/*
					$adjustedCashAmount = $this->dividendAdjuster->getAdjustedDividend(
						$calendarEntry ? $calendarEntry->getCashAmount() : 0.0,
						$calendarEntry->getCreatedAt(),
						$ticker->getCorporateActions()
					);
					*/

					$payout =
						$snapshot->getOwnedQuantity() *
						$adjustedCashAmount *
						(1 - $taxRate) *
						$exchangeRate;

					$payouts[] = [
						'ticker' => $ticker->getSymbol(),
						'fullname' => $ticker->getFullname(),
						'quantity' => $snapshot->getOwnedQuantity(),
						'pieLabel' => $snapshot
							->getTrading212PieMetaData()
							->getPie()
							->getLabel(),
						'payout' => $payout,
						'paymentDate' => $calendarEntry->getPaymentDate(),
						'exDate' => $calendarEntry->getExDividendDate(),
						'taxWithheld' => $taxRate,
						'exchangeRate' => $exchangeRate,
						'originalCashAmount' => $calendarEntry->getCashAmount(),
						'adjustedCashAmount' => $adjustedCashAmount,
						'dividendType' => $calendarEntry->getDividendType(),
						'currency' => $calendarEntry
							->getCurrency()
							->getSymbol(),
					];
				}
			}
		}

		return $payouts;
	}

	public function getForecastGraphData($startDate, $endDate)
	{
		$monthlyPayouts = [];

		for ($date = $startDate; $date <= $endDate; $date->modify('+1 month')) {
			$monthlyPayouts[
				$date->format('Y-m')
			] = $this->calculateProjectedPayouts($date);
		}

		return $monthlyPayouts;
	}
}
