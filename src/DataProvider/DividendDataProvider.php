<?php

namespace App\DataProvider;

use App\Repository\DividendCalendarRepository;
use App\Entity\Ticker;

class DividendDataProvider
{
	public function __construct(
		private DividendCalendarRepository $diviRepo
	) {
	}

	/**
	 * Loads the data for an decorator
	 *
	 * @param Ticker[] $tickers
	 *
	 * @return array<int, array<int, \App\Entity\Calendar>>
	 */
	public function load(array $tickers): array
	{
		$ids = array_map(fn(Ticker $t) => $t->getId(), $tickers);

		return $this->mapByTicker(
				$this->diviRepo->findByTickerIds($ids)
		);
	}

	private function mapByTicker(array $entities): array
	{
		$map = [];
		foreach ($entities as $entity) {
			$pid = $entity->getTicker()->getPositions()->first()->getId();
			$map[$pid][$entity->getId()] = $entity;
		}
		return $map;
	}
}
