<?php

namespace App\Service\Dividend;

use App\Entity\Calendar;
use App\Util\Constants;
use App\Entity\Pie;
use App\Entity\Position;
use App\Entity\Ticker;
use App\Entity\Transaction;
use App\Repository\DividendCalendarRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;

use App\Decorator\Factory\AdjustedDividendDecoratorFactory;
use App\Decorator\Factory\AdjustedPositionDecoratorFactory;
use App\Service\ExchangeRate\DividendExchangeRateResolverInterface;
use App\Service\Transaction\ShareEligibilityCalculatorInterface;
use App\Service\ExchangeRate\ExchangeAndTaxResolverInterface;

class DividendService implements DividendServiceInterface
{
	/**
	 * Net dividend over the shares
	 *
	 * @var null|float
	 */
	protected null|float $forwardNetDividend;

	/**
	 * Position
	 *
	 * @var Position
	 */
	protected Position $position;

	/**
	 * What is the net dividend per payout per share
	 *
	 * @var null|float
	 */
	protected null|float $netDividendPerShare = 0.0;

	/**
	 * Should all dividend paid on same day to same ticker be accumulated?
	 * Normal dividend + Supplement dividend, etc
	 *
	 * @var boolean
	 */
	protected bool $accumulateDividendAmount = true;

	protected float $netDividendYield = 0.0;

	protected ?array $cachedPositionData;

	/**
	 * @var array<int, array<int, \App\Entity\Transaction>>|null
	 */
	protected ?array $cachedTransactions;

	/**
	 * @var array<int, array<int, \App\Entity\CorporateAction>>|null
	 */
	protected ?array $cachedCorporateActions;

	/**
	 * @var array<int, array<int, \App\Entity\Calendar>>|null
	 */
	protected ?array $cachedDividendCalendars;

	public function __construct(
		protected DividendExchangeRateResolverInterface $dividendExchangeRateResolver,
		protected DividendTaxRateResolverInterface $dividendTaxRateResolver,
		protected ShareEligibilityCalculatorInterface $shareEligibilityCalculator,
		protected ExchangeAndTaxResolverInterface $exchangeAndTaxResolver,
		/* protected PositionDataProvider $positionDataProvider,
		protected CorporateActionDataProvider $corporateActionDataProvider,
		protected DividendDataProvider $dividendDataProvider, */
		protected AdjustedPositionDecoratorFactory $adjustedPositionDecoratorFactory,
		protected AdjustedDividendDecoratorFactory $adjustedDividendDecoratorFactory,
		protected DividendCalendarRepository $dividendCalendarRepository
	) {
	}

	public function load(array $transactions, array $corporateActions, array $dividends): self
	{
		$this->cachedTransactions = $transactions;
		$this->cachedCorporateActions = $corporateActions;
		$this->cachedDividendCalendars = $dividends;

		return $this;
	}

	/**
	 * Loads the data for an decorator or returns it from cache if it is already loaded
	 * Returns data based on Position::id
	 *
	 * @param Position $position
	 *
	 * @return array{
	 *     transactions: array<int, array<int, \App\Entity\Transaction>>,
	 *     actions: array<int, array<int, \App\Entity\CorporateAction>>,
	 *     dividends: array<int, array<int, \App\Entity\Calendar>>,
	 * }
	 */
	protected function getChachedDataForPosition(Position $position): array {
		$pid = $position->getId();

		$transactions = $this->cachedTransactions ?? [];
		$actions = $this->cachedCorporateActions ?? [];
		$dividends = $this->cachedDividendCalendars ?? [];

		$this->cachedPositionData[$pid] = ['transactions' => $transactions, 'actions' => $actions, 'dividends' => $dividends];

		return $this->cachedPositionData[$pid];
	}


	/**
	 * Get the exchange rate and tax rate
	 *
	 * @param Position $position
	 *
	 * @param Calendar $calendar
	 *
	 * @return array
	 *
	 * @deprecated Use ExchangeAndTaxResolver::class or ExchangeAndTaxResolverInterface
	 */
	public function getExchangeAndTax(
		Position $position,
		Calendar $calendar
	): array {
		$exchangeRate = 1;
		$dividendTax = 0.15;
		$ticker = $position->getTicker();

		$dividendTax = $ticker->getTax()
			? $ticker->getTax()->getTaxRate()
			: Constants::TAX / 100;
		$exchangeRate = $this->dividendExchangeRateResolver->getRateForCalendar(
			$calendar
		);

		return [$exchangeRate, $dividendTax];
	}

	/**
	 * Which amount of shares should be considered for the dividend on a certain date
	 *
	 * @param Collection $transactions
	 *
	 * @param Calendar $calendar
	 *
	 * @return null|float
	 *
	 * @deprecated Use App\Service\ShareEligibilityCalculator::calculate(Collection $transactions, Calendar $calendar): float
	 */
	public function getPositionSize(
		Collection $transactions,
		Calendar $calendar
	): ?float {
		$shares = 0.0;

		foreach ($transactions as $transaction) {
			if (
				$transaction->getTransactionDate() >=
				$calendar->getExdividendDate()
			) {
				continue;
			}
			$amount = $transaction->getAmount();
			if ($transaction->getSide() === Transaction::BUY) {
				$shares += $amount;
			}
			if ($transaction->getSide() === Transaction::SELL) {
				$shares -= $amount;
			}
		}

		return $shares;
	}

	/**
	 * Get the first regular dividend calendar item. No special or suplement dividends.
	 *
	 * @param Ticker $ticker
	 * @return null|Calendar
	 */
	public function getRegularCalendar(Ticker $ticker): ?Calendar
	{
		if (!$ticker->hasCalendar()) {
			return null;
		}

		$calendars = $ticker->getCalendars()->slice(0, 8);
		$calendars = array_filter($calendars, function ($element) {
			return $element->getDividendType() === Calendar::REGULAR ||
				$element->getDividendType() === null;
		});

		if (count($calendars) > 0) {
			reset($calendars);
			return current($calendars);
		}

		return null;
	}

	/**
	 * How many shares are applicable on ex dividenddate
	 *
	 * @param Calendar $calendar
	 *
	 * @return float|null
	 */
	public function getPositionAmount(Calendar $calendar): ?float
	{
		$ticker = $calendar->getTicker();
		$position = $ticker->getPositions()->first();
		if (!$position) {
			return 0.0;
		}

		$data = $this->getChachedDataForPosition($position);
		$this->adjustedPositionDecoratorFactory->load($data['transactions'], $data['actions']);
		$decoratePosition = $this->adjustedPositionDecoratorFactory->decorate($position);

		return $decoratePosition->getAdjustedAmountPerDate($calendar->getExDividendDate());
	}

	/**
	 * How many shares are applicable on ex dividenddate
	 *
	 * @param Position $position
	 * @return float|null
	 */
	public function getSharesPerPositionAmount(Position $position): ?float
	{

		$data = $this->getChachedDataForPosition($position);
		$this->adjustedPositionDecoratorFactory->load($data['transactions'], $data['actions']);
		$decoratePosition = $this->adjustedPositionDecoratorFactory->decorate($position);

		return $decoratePosition->getAdjustedAmount();
	}


	/**
	 * Get the net dividend payout. Todo return adjusted and unadjusted cashAmount
	 *
	 * @param Calendar $calendar
	 * @return float|null
	 */
	public function getNetDividend(
		Position $position,
		Calendar $calendar
	): ?float {
		$ticker = $position->getTicker();
		$dividendTax = $ticker->getTax()
			? $ticker->getTax()->getTaxRate()
			: Constants::TAX / 100;

		$cashAmount = $calendar->getCashAmount();
		if (!isset($this->cachedDividendCalendars[$position->getId()])) {
			return 0.0;
		}
		$this->adjustedDividendDecoratorFactory->load($this->cachedDividendCalendars, $this->cachedCorporateActions);
		$dividendDecorator = $this->adjustedDividendDecoratorFactory->decorate($position->getTicker());
		$adjustedDividends = $dividendDecorator->getAdjustedDividend();

		try{
			$adjustedCashAmount = $adjustedDividends[$calendar->getId()]['adjusted'];
		} catch (\Exception $e) {
			//dd($this, $position->getId(), $position->getTicker(), $adjustedDividends, $calendar->getId());
			throw $e;
		}
		if ($this->accumulateDividendAmount) {
			$cashAmount = $this->getCashAmount($ticker);
		}
		$exchangeRate = $this->dividendExchangeRateResolver->getRateForCalendar(
			$calendar
		);

		return $adjustedCashAmount * (1 - $dividendTax) * $exchangeRate;

		//return $cashAmount * (1 - $dividendTax) * $exchangeRate;
	}

	/**
	 * Get total net dividend on calender ex div date
	 *
	 * @param Calendar $calendar
	 * @return float|null
	 */
	public function getTotalNetDividend(Calendar $calendar): ?float
	{
		$dividend = 0.0;

		$ticker = $calendar->getTicker();
		$positions = $ticker->getPositions();
		if (count($positions) > 0) {
			$position = $positions->first();
			if (!$position) {
				return $dividend;
			}
			$amount = $this->getPositionAmount($calendar);

			if ($amount > 0) {
				$netDividend = $this->getNetDividend($position, $calendar);
				$dividend = $amount * $netDividend;
			}
		}

		return $dividend;
	}

	/**
	 * Return adjusted decalred cash amount.
	 *
	 * @param Ticker $ticker
	 */
	private function resolveCashAmount(Ticker $ticker): float
	{

		$position = $ticker->getPositions()->first();

		$this->adjustedDividendDecoratorFactory->load(dividends: $this->cachedDividendCalendars, actions: $this->cachedCorporateActions);

		$dividendDecorator = $this->adjustedDividendDecoratorFactory->decorate($position->getTicker());
		$dividends = $dividendDecorator->getAdjustedDividend();
		$dividendList = new ArrayCollection($dividends);
		$lastDividend = $dividendList->last();

		$adjustedCashAmount = $lastDividend['adjusted'] ?? 0.0;

		return $adjustedCashAmount;
	}

	/**
	 *
	 * @param Ticker $ticker
	 *
	 * @return float|null
	 */
	public function getCashAmount(Ticker $ticker): ?float
	{
		$cashAmount = 0;
		$calendars = $ticker->getCalendars();
		if (count($calendars) > 0) {
			$cashAmount = $this->resolveCashAmount($ticker);
		}

		return $cashAmount;
	}

	/**
	 * Get the expected regular dividend for the next dividend payout date
	 *
	 * @param Ticker $ticker
	 *
	 * @param float $amount
	 *
	 * @return float|null
	 */
	public function getForwardNetDividend(Ticker $ticker, float $amount): ?float
	{
		$cashAmount = 0.0;
		$forwardNetDividend = 0.0;
		$calendars = $ticker->getCalendars();
		if (count($calendars) > 0) {
			/**
			 * @var \App\Entity\Calendar $calendar
			 */
			$calendar = $this->getRegularCalendar($ticker);
			if ($calendar) {
				$cashAmount = $this->resolveCashAmount($ticker);
				$dividendTax = $ticker->getTax()
					? $ticker->getTax()->getTaxRate()
					: Constants::TAX / 100;
				$exchangeRate = $this->dividendExchangeRateResolver->getRateForCalendar(
					$calendar
				);

				$this->netDividendPerShare =
					$cashAmount * $exchangeRate * (1 - $dividendTax);

				$forwardNetDividend =
					(float) $amount *
					$cashAmount *
					$exchangeRate *
					(1 - $dividendTax);
			}
		}
		$this->forwardNetDividend = $forwardNetDividend;

		return $forwardNetDividend;
	}

	/**
	 * @deprecated: Use DividendCalendarService::class
	 */
	public function getCalendarDataPerMonth(
		array $calendars,
		?string $startDate = null,
		?string $endDate = null,
		?Pie $pie = null
	): array {
		$data = [];
		$this->setAccumulateDividendAmount(false);

		foreach ($calendars as $calendar) {

			$positionAmount = $this->getPositionAmount($calendar);

			if ($positionAmount < 0.001) {
				// filter out ones that have no amount of stocks for dividend payout
				continue;
			}

			$positionDividend = $this->getTotalNetDividend($calendar);

			if ($positionDividend < 0.01) {
				// filter out ones that have no payout of dividend or to small to matter
				continue;
			}

			$ticker = $calendar->getTicker()->getSymbol();
			$position = $calendar->getTicker()->getPositions()->first();
			$taxRate = $this->dividendTaxRateResolver->getTaxRateForCalendar($calendar);
			$exchangeRate = $this->dividendExchangeRateResolver->getRateForCalendar(
				$calendar
			);

			$dividendDecorator = $this->adjustedDividendDecoratorFactory->decorate($position->getTicker());
			$dividends = $dividendDecorator->getAdjustedDividend();
			$adjustedCashAmount = $dividends[$calendar->getId()]['adjusted'];

			$tax = $adjustedCashAmount * $exchangeRate * $taxRate;

			$data[$calendar->getPaymentDate()->format('Ym')][
				$calendar->getPaymentDate()->format('j')
			][] = [
				'calendar' => $calendar,
				'ticker' => $ticker,
				'positionAmount' => $positionAmount,
				'positionDividend' => $positionDividend,
				'taxRate' => $taxRate,
				'exchangeRate' => $exchangeRate,
				'tax' => $tax,
				'adjustedDividendCash' => $adjustedCashAmount,
			];
		}
		ksort($data);
		foreach ($data as &$month) {
			ksort($month);
		}
		return $data;
	}

	/**
	 * What will be the yield based on the last dividend payout
	 *
	 * @param Position $position
	 * @return float|null
	 */
	public function getForwardNetDividendYield(
		Position $position,
		Ticker $ticker,
		float $amount,
		float $allocation
	): ?float {
		if ($position->getClosed() == true) {
			return null;
		}

		$netDividendYield = 0.0;
		$forwardNetDividend = $this->getForwardNetDividend(
			$position->getTicker(),
			$amount
		);



		if ($forwardNetDividend) {
			$dividendFrequency = 4;
			if ($position->getTicker()->getDividendMonths()) {
				$dividendFrequency = $position
					->getTicker()
					->getPayoutFrequency();
			}
			$totalNetDividend = $forwardNetDividend * $dividendFrequency;

			$netDividendYield = round(
				($totalNetDividend / $allocation) * 100,
				2
			);
		}

		$this->netDividendYield = $netDividendYield;
		return $netDividendYield;
	}

	/**
	 * Get what is the net dividend per payout per share
	 *
	 * @return  null|float
	 */
	public function getNetDividendPerShare(?Position $position): ?float
	{
		if (!$this->netDividendPerShare && $position) {
			$data = $this->getChachedDataForPosition($position);
			$this->adjustedPositionDecoratorFactory->load($data['transactions'], $data['actions']);
			$positionDecorator = $this->adjustedPositionDecoratorFactory->decorate($position);
			$amount = $positionDecorator->getAdjustedAmount();

			$this->getForwardNetDividend(
				$position->getTicker(),
				$amount ,//$position->getAmount()
			);
		}

		return $this->netDividendPerShare;
	}

	/**
	 * Set normal dividend + Supplement dividend, etc
	 * Normal dividend + Supplement dividend, etc
	 *
	 * @param  boolean  $accumulateDividendAmount
	 *
	 * @return  self
	 */
	public function setAccumulateDividendAmount(
		bool $accumulateDividendAmount = true
	): self {
		$this->accumulateDividendAmount = $accumulateDividendAmount;

		return $this;
	}
}
