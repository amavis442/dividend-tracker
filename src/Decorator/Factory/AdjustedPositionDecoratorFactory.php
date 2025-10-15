<?php

namespace App\Decorator\Factory;

use App\Entity\Position;
use App\Decorator\AdjustedPositionDecorator;
use App\Decorator\AdjustedPositionDecoratorInterface;
use App\Service\Transaction\TransactionAdjusterInterface;

class AdjustedPositionDecoratorFactory
{
	/**
	 * @var array<int, array<int, \App\Entity\Transaction>> $transactions
	 */
	private ?array $transactions = null;
	/**
	 * @var array<int , array<int, \App\Entity\CorporateAction>> $actions
	 */
	private ?array $actions = null;

	public function __construct(
		private TransactionAdjusterInterface $transactionAdjuster
	) {
	}

	public function setActions(array $actions): self
	{
		$this->actions = $actions;

		return $this;
	}

	public function setTransactions(array $transactions): self
	{
		$this->transactions = $transactions;

		return $this;
	}

	/**
	 * Mass load needed data for decorator(s)
	 *
	 * @param array<int, array<int, \App\Entity\Transaction>> $transactions key is positionId
	 *
	 * @param array<int , array<int, \App\Entity\CorporateAction>> $actions key is tickerId
	 *
	 * @return self
	 *
	 */
	public function load(array $transactions, array $actions): self
	{
		$this->transactions = $transactions;
		$this->actions = $actions;

		return $this;
	}

	/**
	 * Returns a decorator for 1 specific position
	 *
	 * @param Position $position
	 *
	 * @return AdjustedPositionDecoratorInterface
	 */
	public function decorate(
		Position $position,
		bool $byTicker = false
	): AdjustedPositionDecoratorInterface {
		$pid = $position->getId();
		$tid = $position->getTicker()->getId();

		return new AdjustedPositionDecorator(
			position: $position,
			transactions: $this->transactions[$byTicker ? $tid : $pid] ?? [],
			actions: $this->actions[$tid] ?? [],
			transactionAdjuster: $this->transactionAdjuster
		);
	}

	/**
	 * Returns an array of decorators for all specified positions.
	 *
	 * @return array
	 */
	public function decorateBatch(array $positions): array
	{
		return array_map(
			fn($position) => $this->decorate($position),
			$positions
		);
	}
}
