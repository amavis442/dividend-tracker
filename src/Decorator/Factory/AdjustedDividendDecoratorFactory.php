<?php
namespace App\Decorator\Factory;

use App\Entity\Position;
use App\Decorator\AdjustedDividendDecorator;
use App\Decorator\AdjustedDividendDecoratorInterface;
use App\Service\DividendAdjuster;

class AdjustedDividendDecoratorFactory
{
	private ?array $dividends = null;
	private ?array $actions = null;

	public function __construct(
        private DividendAdjuster $dividendAdjuster,
	) {
	}

	public function setActions(array $actions): self
	{
		$this->actions = $actions;

		return $this;
	}

	public function setDividends(array $dividends): self
	{
		$this->dividends = $dividends;

		return $this;
	}

	/**
	 * Mass load needed data for decorator(s)
	 *
	 * @param array<int, array<int, \App\Entity\Calendar>> $dividends
	 *
	 * @param array<int , array<int, \App\Entity\CorporateAction>> $actions
	 *
	 * @return self
	 *
	 */
	public function load(array $dividends, array $actions): self
	{
		$this->dividends = $dividends;
		$this->actions = $actions;

		return $this;
	}


	public function decorate(Position $position): AdjustedDividendDecoratorInterface
	{
		$pid = $position->getId();

		return new AdjustedDividendDecorator(
			position: $position,
			dividends: $this->dividends[$pid] ?? [],
			actions: $this->actions[$pid] ?? [],
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
