<?php
namespace App\Decorator;

use App\Entity\Position;
use App\Entity\Calendar;
use App\Repository\CorporateActionRepository;
use App\Repository\DividendCalendarRepository;
use App\Service\DividendAdjuster;
use Doctrine\Common\Collections\ArrayCollection;

class AdjustedDividendDecorator
{
	private ?array $cachedDividends = null;
	private ?array $cachedActions = null;

	public function __construct(
		private Position $position,
		private DividendCalendarRepository $dividendRepo,
		private CorporateActionRepository $actionRepo,
		private DividendAdjuster $dividendAdjuster
	) {
	}

	/**
	 * Caches the dividends so it will not waste resources
	 */
	private function getDividends(): array
	{
		if ($this->cachedDividends === null) {
			$this->cachedDividends = $this->dividendRepo->findBy([
				'ticker' => $this->position->getTicker()->getId(),
			],['paymentDate' => 'ASC']);
		}

		return $this->cachedDividends;
	}

	/**
	 * Caches the actions so it will not waste resources
	 */
	private function getActions(): array
	{
		if ($this->cachedActions === null) {
			$this->cachedActions = $this->actionRepo->findBy(
				[
					'position' => $this->position->getId(),
					'type' => 'reverse_split',
				],
				['eventDate' => 'ASC']
			);
		}

		return $this->cachedActions;
	}

	public function getAdjustedDividend(): array
	{
		$dividends = $this->getDividends();
		$actions = new ArrayCollection($this->getActions());

		$adjusted = [];

		foreach ($dividends as $dividend) {
			$adjusted[] = [
				'original' => $dividend->getCashAmount(),
				'adjusted' => $this->dividendAdjuster->getAdjustedDividend(
					$dividend->getCashAmount(),
					$dividend->getCreatedAt(),
					$actions
				),
				'declareDate' => $dividend->getCreatedAt(),
				'paymentDate' => $dividend->getPaymentDate(),
				'ticker' => $dividend->getTicker()->getSymbol(),
			];
		}

		return $adjusted;
	}

	public function getAdjustmentNote(): ?string
	{
		$actions = $this->actionRepo->findBy(
			['position' => $this->position->getId(), 'type' => 'reverse_split'],
			['eventDate' => 'ASC']
		);

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
