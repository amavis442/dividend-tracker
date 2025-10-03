<?php

namespace App\DataProvider;

use App\Repository\TransactionRepository;
use App\Repository\CorporateActionRepository;
use App\Entity\Position;

class PositionDataProvider
{
	public function __construct(
		private TransactionRepository $txRepo,
		private CorporateActionRepository $actionRepo
	) {
	}

	/**
	 * Loads the data for an decorator
	 *
	 * @param Position[] $positions
	 *
	 * @return array{
	 *     transactions: array<int, array<int, \App\Entity\Transaction>>,
	 *     actions: array<int, array<int, \App\Entity\CorporateAction>>
	 * }
	 */
	public function load(array $positions): array
	{
		$ids = array_map(fn(Position $p) => $p->getId(), $positions);

		return [
			'transactions' => $this->mapByPosition(
				$this->txRepo->findByPositionIds($ids)
			),
			'actions' => $this->mapByPosition(
				$this->actionRepo->findByPositionIds($ids)
			),
		];
	}

	private function mapByPosition(array $entities): array
	{
		$map = [];
		foreach ($entities as $entity) {
			$pid = $entity->getPosition()->getId();
			$map[$pid][] = $entity;
		}
		return $map;
	}
}
