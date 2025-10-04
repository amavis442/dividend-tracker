<?php
namespace App\Decorator;

use App\Entity\Position;
use App\Entity\CorporateAction;
use App\Service\DividendAdjuster;
use Doctrine\Common\Collections\ArrayCollection;

class AdjustedDividendDecorator implements AdjustedDecoratorInterface, AdjustedDividendDecoratorInterface
{
	public function __construct(
		private Position $position,
		private array $dividends,
		private array $actions,
		private DividendAdjuster $dividendAdjuster
	) {
	}

	public function getAdjustedDividend(): array
	{
		$dividends = $this->dividends;
		$actions = new ArrayCollection($this->actions);

		$adjusted = [];

		foreach ($dividends as $dividend) {
			$adjusted[$dividend->getId()] = [
				'original' => $dividend->getCashAmount(),
				'adjusted' => $this->dividendAdjuster->getAdjustedDividend(
					$dividend->getCashAmount(),
					$dividend->getCreatedAt(),
					$actions
				),
				'declareDate' => $dividend->getCreatedAt(),
				'paymentDate' => $dividend->getPaymentDate(),
				'ticker' => $dividend->getTicker(),
				'symbol' => $dividend->getTicker()->getSymbol(),
			];
		}

		return $adjusted;
	}

	public function getAdjustmentNote(): ?string
	{
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
