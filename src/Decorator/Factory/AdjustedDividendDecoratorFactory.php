<?php
namespace App\Decorator\Factory;

use App\Entity\Ticker;
use App\Decorator\AdjustedDividendDecorator;
use App\Decorator\AdjustedDividendDecoratorInterface;
use App\Service\Dividend\DividendAdjuster;

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


	public function decorate(Ticker $ticker): AdjustedDividendDecoratorInterface
	{
		$tid = $ticker->getId();

		return new AdjustedDividendDecorator(
			dividends: $this->dividends[$tid] ?? [],
			actions: $this->actions[$tid] ?? [],
			dividendAdjuster: $this->dividendAdjuster
		);
	}

	/**
	 * Optionally decorate a batch of tickers
	 */
	public function decorateBatch(array $tickers): array
	{
		return array_map(
			fn($ticker) => $this->decorate($ticker),
			$tickers
		);
	}
}
