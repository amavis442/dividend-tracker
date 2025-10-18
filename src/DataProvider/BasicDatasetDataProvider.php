<?php

namespace App\DataProvider;

use App\DataProvider\DividendDataProvider;
use App\Entity\Calendar;
use App\Repository\PositionRepository;
use App\Repository\TransactionRepository;
use App\Service\Dividend\DividendAdjuster;
use App\Service\ExchangeRate\DividendExchangeRateResolverInterface;
use App\Service\Transaction\TransactionAdjuster;
use App\Dto\BasicDataSetDto;
use App\Decorator\Factory\AdjustedPositionDecoratorFactory;

class BasicDatasetDataProvider
{
	const CALENDAR_DATA_TYPE = 1;
	const YIELD_DATA_TYPE = 2;

	public function __construct(
		protected PositionRepository $positionRepository,
		protected TransactionRepository $transactionRepository,
		protected CorporateActionDataProvider $corporateActionDataProvider,
		protected DividendExchangeRateResolverInterface $dividendExchangeRateResolver,
		protected TransactionAdjuster $transactionAdjuster,
		protected DividendAdjuster $dividendAdjuster,
		protected DividendDataProvider $dividendDataProvider,
        protected AdjustedPositionDecoratorFactory $adjustedPositionDecoratorFactory,
	) {
	}

	/**
	 * Returns basic set needed for portfolio, dividend calendar and yield calculations
	 * If the dates are not set it will look for calendar data between now - 3 months and now + 3 months
	 *
	 * @param null|\DateTime $calendarStartDate
	 * @param null|\DateTime $calendarEndDate
	 * @param array $calendarTypes
	 *
	 * @return BasicDataSetDto
	 *
	 */
	private function getDataSet(
		int $dataType = self::CALENDAR_DATA_TYPE,
		?\DateTime $calendarStartDate = null,
		?\DateTime $calendarEndDate = null,
		array $calendarTypes = [Calendar::REGULAR]
	): BasicDataSetDto {
		$positionData = match ($dataType) {
			self::CALENDAR_DATA_TYPE
				=> $this->positionRepository->getForCalendarView(),
			self::YIELD_DATA_TYPE
				=> $this->positionRepository->getForYieldView(),
			default => $this->positionRepository->getForYieldView()
		};

		$positionIds = array_map(function ($position) {
			return $position->getId();
		}, $positionData);

		$tickers = [];
		$tickerIds = [];
		$positions = [];
		foreach ($positionData as $position) {
			$ticker = $position->getTicker();
			$tickers[$ticker->getId()] = $ticker;
			$tickerIds[] = $ticker->getId();
			$positions[$ticker->getId()] = $position;
		}

		$transactionData = $this->transactionRepository->findByPositionIds(
			$positionIds
		);

		unset($positionIds);
		unset($positionData);

		$transactions = [];
		foreach ($transactionData as $transaction) {
			$tickerId = $transaction->getPosition()->getTicker()->getId();
            $transactions[$tickerId][] = $transaction;
		}

		$corporateActions = $this->corporateActionDataProvider->load($tickers);

        foreach (array_keys($corporateActions) as $tickerId) {
            $position = $positions[$tickerId];
            $adjustedPositionDecorator =  $this->adjustedPositionDecoratorFactory->load($transactions, $corporateActions);
            $positionDecorator = $adjustedPositionDecorator->decorate($position, true);

            $adjustedAmount = $positionDecorator->getAdjustedAmount();
            $position->setAdjustedAmount($adjustedAmount);
			$adjustedAveragePrice = $positionDecorator->getAdjustedAveragePrice();
            $position->setAdjustedAveragePrice($adjustedAveragePrice);
        }

		$calendars = $this->dividendDataProvider->load(
			tickers: $tickers,
			afterDate: $calendarStartDate ?? new \DateTime('-4 month'),
			beforeDate: $calendarEndDate ?? new \DateTime('+4 month'),
			types: $calendarTypes
		);

		$exchangeRates = [];
		foreach ($tickers as $ticker) {
			$tickerId = $ticker->getId();
			$exchangeRates[
				$tickerId
			] = $this->dividendExchangeRateResolver->getRateForTicker($ticker);

			if (isset($calendars[$tickerId]) && isset($corporateActions[$tickerId])) {
				foreach ($calendars[$tickerId] as $calendar) {
					$this->dividendAdjuster->getAdjustedDividend(
						$calendar,
						$corporateActions[$tickerId] ?? []
					);
				}
			}
		}

        return new BasicDatasetDto($tickers, $calendars, $transactions, $positions, $exchangeRates);
	}

	/**
	 * Returns basic set needed for portfolio, dividend calendar and yield calculations
	 * If the dates are not set it will look for calendar data between now - 3 months and now + 3 months
	 *
	 * @param null|\DateTime $calendarStartDate
	 * @param null|\DateTime $calendarEndDate
	 * @param array $calendarTypes
	 *
	 * @return BasicDataSetDto
	 *
	 */
	public function getDataForCalendar(
		?\DateTime $calendarStartDate = null,
		?\DateTime $calendarEndDate = null,
		array $calendarTypes = [Calendar::REGULAR]
	): BasicDataSetDto {
		return $this->getDataSet(
			self::CALENDAR_DATA_TYPE,
			$calendarStartDate,
			$calendarEndDate,
			$calendarTypes
		);
	}

	/**
	 * Returns basic set needed for portfolio, dividend calendar and yield calculations
	 * If the dates are not set it will look for calendar data between now - 3 months and now + 3 months
	 *
	 * @param null|\DateTime $calendarStartDate
	 * @param null|\DateTime $calendarEndDate
	 * @param array $calendarTypes
	 *
	 * @return BasicDataSetDto
	 *
	 */
	public function getDataForYield(
		?\DateTime $calendarStartDate = null,
		?\DateTime $calendarEndDate = null,
		array $calendarTypes = [Calendar::REGULAR]
	): BasicDataSetDto {
		return $this->getDataSet(
			self::YIELD_DATA_TYPE,
			$calendarStartDate,
			$calendarEndDate,
			$calendarTypes
		);
	}
}
