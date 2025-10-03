<?php
namespace App\Decorator\Factory;

use App\Entity\Position;
use App\Decorator\AdjustedDividendDecorator;
use App\Decorator\AdjustedDividendDecoratorInterface;
use App\Repository\DividendCalendarRepository;
use App\Repository\CorporateActionRepository;
use App\Service\DividendAdjuster;

class AdjustedDividendDecoratorFactory
{
	public function __construct(
		private DividendCalendarRepository $dividendRepo,
		private CorporateActionRepository $actionRepo,
        private DividendAdjuster $dividendAdjuster,
	) {
	}

	public function decorate(Position $position): AdjustedDividendDecoratorInterface
	{
		return new AdjustedDividendDecorator(
			position: $position,
			dividendRepo: $this->dividendRepo,
			actionRepo: $this->actionRepo,
			dividendAdjuster: $this->dividendAdjuster
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
