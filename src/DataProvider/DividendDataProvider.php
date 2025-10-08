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
	 * Loads the data for an decorator keyed with \App\Entity\Ticker::Id()
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

	private function mapByTicker(mixed $calendars): array
	{
		$map = [];
		foreach ($calendars as $calendar) {
			$tid = $calendar->getTicker()->getId();
			$map[$tid][$calendar->getId()] = $calendar;
		}
		return $map;
	}
}
