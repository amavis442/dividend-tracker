<?php

namespace App\Service;

use App\Repository\Trading212PieInstrumentRepository;
use App\Repository\DividendCalendarRepository;
use App\Service\ExchangeAndTaxResolver;

class DividendForecastService
{
	public function __construct(
		protected Trading212PieInstrumentRepository $holdingsSnapshotInstrumentRepository,
		protected DividendCalendarRepository $dividendCalendarRepository,
		protected ExchangeAndTaxResolver $exchangeAndTaxResolver
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

		foreach ($snapshots as $snapshot) {
			$ticker = $snapshot->getTicker();

			$calendarEntries = $this->dividendCalendarRepository->getEntriesByTickerAndPayoutDate(
				$ticker,
				$snapshotDate
			);

			foreach ($calendarEntries as $entry) {
				$exchangeTaxDto = $this->exchangeAndTaxResolver->resolve(
					$ticker,
					$entry
				);
				$taxRate = $exchangeTaxDto->taxAmount;
				$exchangeRate = $exchangeTaxDto->exchangeRate;

				if ($snapshot->getCreatedAt() < $entry->getPaymentDate()) {
					$payout =
						$snapshot->getOwnedQuantity() *
						$entry->getCashAmount() *
						(1 - $taxRate) *
						$exchangeRate;
					$payouts[] = [
						'ticker' => $ticker->getSymbol(),
						'fullname' => $ticker->getFullname(),
						'quantity' => $snapshot->getOwnedQuantity(),
						'pie' => $snapshot->getTrading212PieMetaData()->getPie()->getLabel(),
						'payout' => $payout,
						'payDate' => $entry->getPaymentDate(),
						'exDate' => $entry->getExDividendDate(),
						'taxWithheld' => $taxRate,
						'exchangeRate' => $exchangeRate,
						'cashAmount' => $entry->getCashAmount(),
						'dividendType' => $entry->getDividendType(),
						'currency' => $entry->getCurrency()->getSymbol(),
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
