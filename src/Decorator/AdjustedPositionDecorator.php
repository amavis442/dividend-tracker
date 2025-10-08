<?php
namespace App\Decorator;

use App\Entity\Position;
use App\Entity\Transaction;
use App\Service\Transaction\TransactionAdjusterInterface;
use Doctrine\Common\Collections\ArrayCollection;

class AdjustedPositionDecorator implements AdjustedDecoratorInterface,AdjustedPositionDecoratorInterface
{
	private ?ArrayCollection $cachedTransactions = null;
	private ?ArrayCollection $cachedActions = null;

	/**
	 * @param Position $position
	 *
	 * @param array<int, \App\Entity\Transaction> $transactions
	 *
	 * @param array<int, \App\Entity\CorporateAction> $actions
	 *
	 * @param \App\Service\TransactionAdjusterInterface $transactionAdjuster
	 */
	public function __construct(
		private Position $position,
		private array $transactions,
		private array $actions,
		private TransactionAdjusterInterface $transactionAdjuster
	) {
	}

	/**
	 * Caches the transactions so it will not waste resources
	 */
	private function getTransactions(): ArrayCollection
	{
		if ($this->cachedTransactions === null) {
			$this->cachedTransactions = new ArrayCollection($this->transactions);
		}

		return $this->cachedTransactions;
	}

	/**
	 * Caches the actions so it will not waste resources
	 */
	private function getActions(): ArrayCollection
	{
		if ($this->cachedActions === null) {
			$this->cachedActions = new ArrayCollection($this->actions);
		}

		return $this->cachedActions;
	}

	/**
	 * Calculates the adjusted number of shares held for a given position,
	 * taking into account reverse split corporate actions.
	 *
	 * This method retrieves all transactions associated with the current position
	 * and applies reverse split adjustments using the TransactionAdjuster service.
	 * It sums the adjusted amounts, treating BUY transactions as positive and
	 * SELL transactions as negative, to compute the net adjusted share count.
	 *
	 * @return float The total adjusted share amount, rounded to 7 decimal places.
	 */
	public function getAdjustedAmount(): float
	{
		$transactions = $this->getTransactions();
		$actions =$this->getActions();

		$total = 0.0;

		foreach ($transactions as $tx) {
			$adjustedAmount = $this->transactionAdjuster->getAdjustedAmount(
				$tx,
				$actions
			);

			$side = $tx->getSide();
			$total +=
				$side === Transaction::BUY ? $adjustedAmount : -$adjustedAmount;
		}

		return round($total, 7);
	}

	/**
	 * Returns the number of shares brefore a specific cutoff date
	 *
	 * @param \DateTimeInterface $datetime
	 *
	 * @return float
	 */
	public function getAdjustedAmountPerDate(\DateTimeInterface $datetime): float
	{
		$transactions = $this->getTransactions();
		$actions = $this->getActions();

		$total = 0.0;
		$timestamp = $datetime->format('Ymd');

		foreach ($transactions as $tx) {
			 if ($tx->getTransactionDate()->format('Ymd') > $timestamp) {
				continue;
			 }
				$adjustedAmount = $this->transactionAdjuster->getAdjustedAmount(
					$tx,
					$actions
				);

				$side = $tx->getSide();
				$total +=
					$side === Transaction::BUY ? $adjustedAmount : -$adjustedAmount;

		}

		return round($total, 7);
	}

	/**
	 * Calculates the adjusted average purchase price per share for the current position,
	 * factoring in reverse split corporate actions.
	 *
	 * This method retrieves all transactions linked to the position and applies reverse split
	 * ratios to the share amounts if the transaction occurred before the action's event date.
	 * It then computes the total cost and total adjusted shares, treating BUY transactions
	 * as additions and SELL transactions as subtractions.
	 *
	 * The final result is the weighted average price per share after adjustment,
	 * rounded to 4 decimal places. If no shares remain, it returns 0.0.
	 *
	 * @return float The adjusted average price per share, or 0.0 if no shares remain.
	 */
	public function getAdjustedAveragePrice(): float
	{
		$transactions = $this->getTransactions();
		$actions = $this->getActions();

		$totalShares = 0.0;
		$totalCost = 0.0;

		foreach ($transactions as $tx) {
			$amount = $tx->getAmount();
			$price = $tx->getPrice();
			$txDate = $tx->getTransactionDate();

			foreach ($actions as $action) {
				if ($txDate < $action->getEventDate()) {
					$amount *= $action->getRatio();
                    $price /= $action->getRatio();
				}
			}

			$side = $tx->getSide();
			if ($side === 1) {
				$totalShares += $amount;
				$totalCost += $amount * $price;
			} elseif ($side === 2) {
				$totalShares -= $amount;
				$totalCost -= $amount * $price;
			}
		}

		return $totalShares > 0 ? round($totalCost / $totalShares, 4) : 0.0;
	}


	public function getAdjustedAveragePricePerDate(\DateTimeInterface $datetime): float
	{
		$transactions = $this->getTransactions();
		$actions = $this->getActions();

		$totalShares = 0.0;
		$totalCost = 0.0;
		$timestamp = $datetime->format('Ymd');

		foreach ($transactions as $tx) {
			if ($tx->getTransactionDate()->format('Ymd') > $timestamp) {
				continue;
			}

			$amount = $tx->getAmount();
			$price = $tx->getPrice();
			$txDate = $tx->getTransactionDate();

			foreach ($actions as $action) {
				if ($txDate < $action->getEventDate()) {
					$amount *= $action->getRatio();
                    $price /= $action->getRatio();
				}
			}

			$side = $tx->getSide();
			if ($side === 1) {
				$totalShares += $amount;
				$totalCost += $amount * $price;
			} elseif ($side === 2) {
				$totalShares -= $amount;
				$totalCost -= $amount * $price;
			}
		}

		return $totalShares > 0 ? round($totalCost / $totalShares, 4) : 0.0;
	}


	public function getAdjustmentNote(): ?string
	{
		/* $actions = $this->actionRepo->findBy(
			['position' => $this->position->getId(), 'type' => [CorporateAction::REVERSE_SPLIT, CorporateAction::SPLIT]],
			['eventDate' => 'ASC']
		); */
		$actions = $this->actions;

		if (empty($actions)) {
			return null;
		}

		$notes = array_map(function ($action) {
			return sprintf(
				'Adjusted due to reverse split on %s (ratio: %s)',
				$action->getEventDate()->format('Y-m-d'),
				$action->getRatio()
			);
		}, $actions);

		return implode('; ', $notes);
	}

	public function getOriginalPosition(): Position
	{
		return $this->position;
	}

	public function getSymbol(): string
	{
		return $this->position->getTicker()->getSymbol();
	}
}
