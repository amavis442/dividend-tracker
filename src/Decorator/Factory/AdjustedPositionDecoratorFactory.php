<?php

namespace App\Decorator\Factory;

use App\Entity\Position;
use App\Decorator\AdjustedPositionDecorator;
use App\Repository\TransactionRepository;
use App\Repository\CorporateActionRepository;
use App\Service\TransactionAdjuster;

class AdjustedPositionDecoratorFactory
{
	public function __construct(
		private TransactionRepository $transactionRepo,
		private CorporateActionRepository $actionRepo,
        private TransactionAdjuster $transactionAdjuster,
	) {
	}

	public function decorate(Position $position): AdjustedPositionDecorator
	{
		return new AdjustedPositionDecorator(
			position: $position,
			transactionRepo: $this->transactionRepo,
			actionRepo: $this->actionRepo,
			transactionAdjuster: $this->transactionAdjuster
		);
	}

	/**
	 * Optionally decorate a batch of positions
	 */
	public function decorateBatch(array $positions): array
	{
		return array_map(
			fn($position) => $this->decorate($position),
			$positions
		);
	}
}
