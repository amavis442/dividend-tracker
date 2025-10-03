<?php

namespace App\DataProvider;

use App\Repository\CorporateActionRepository;
use App\Entity\Position;

class CorporateActionDataProvider
{
	public function __construct(
		private CorporateActionRepository $actionRepo
	) {
	}

	/**
	 * Loads the data for an decorator
	 *
	 * @param Position[] $positions
	 *
	 * @return array<int, array<int, \App\Entity\CorporateAction>>
	 */
	public function load(array $positions): array
	{
		$ids = array_map(fn(Position $p) => $p->getId(), $positions);

		return  $this->mapByPosition(
				$this->actionRepo->findByPositionIds($ids)
		);
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
