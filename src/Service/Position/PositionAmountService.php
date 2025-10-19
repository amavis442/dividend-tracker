<?php

namespace App\Service\Position;

use App\Entity\Position;
use App\Decorator\Factory\AdjustedPositionDecoratorFactory;

/**
 *
 * @todo Needs refactoring because of change to the factory
 */
class PositionAmountService
{
	private Position $position;
	private array $transactions;
	private array $actions;

	public function __construct(
		private AdjustedPositionDecoratorFactory $adjustedPositionDecoratorFactory,
		) {	}

	public function setPosition(Position $position): self
	{
		$this->position = $position;

		return $this;
	}

	public function load(array $transactions, array $actions): self {
		$this->transactions = $transactions;
		$this->actions = $actions;

		return $this;
	}

	public function getAmount(): float
	{
		$this->adjustedPositionDecoratorFactory->load($this->transactions, $this->actions);
		$decorator = $this->adjustedPositionDecoratorFactory->decorate($this->position);

		return $decorator->getAdjustedAmount();
	}
}
