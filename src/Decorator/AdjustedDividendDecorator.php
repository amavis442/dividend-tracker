<?php
namespace App\Decorator;

use App\Entity\Ticker;
use App\Service\Dividend\DividendAdjuster;
use Doctrine\Common\Collections\ArrayCollection;

class AdjustedDividendDecorator implements AdjustedDecoratorInterface, AdjustedDividendDecoratorInterface
{
	const SORT_BY_CALENDAR_ID = 1;
	const SORT_BY_PAYMENT_DATE = 2;

    public function __construct(
		private array $dividends,
		private array $actions,
		private DividendAdjuster $dividendAdjuster
	) {
	}


	private function getAdjustedDividendByKey(int $key): array
	{
		$dividends = $this->dividends;
		$actions = new ArrayCollection($this->actions);

		$adjusted = [];

		foreach ($dividends as $dividend) {
			$indexBy = $key == self::SORT_BY_CALENDAR_ID ? $dividend->getId() : $dividend->getPaymentDate()->format('Ymd');


			$adjusted[$indexBy] = [
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
				'calendar' => $dividend,
			];
		}

		return $adjusted;


	}
	public function getAdjustedDividend(): array
	{
		return $this->getAdjustedDividendByKey(self::SORT_BY_CALENDAR_ID);
	}

	public function getAdjustedDividendSortByPaymentDate(): array
	{
		$adjusted = $this->getAdjustedDividendByKey(self::SORT_BY_PAYMENT_DATE);
		ksort($adjusted);
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
}
