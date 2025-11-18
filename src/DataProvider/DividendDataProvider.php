<?php

namespace App\DataProvider;

use App\Repository\DividendCalendarRepository;
use App\Entity\Ticker;
use App\Entity\Calendar;
use Psr\Log\LoggerInterface;

class DividendDataProvider
{
	public function __construct(
		private DividendCalendarRepository $diviRepo,
		private LoggerInterface $logger
	) {
	}

	/**
	 * Loads the data for an decorator keyed with \App\Entity\Ticker::Id()
	 *
	 * @param Ticker[] $tickers
	 *
	 * @return array<int, array<int, \App\Entity\Calendar>>
	 */
	public function load(array $tickers, ?\DateTime $afterDate = null, ?\DateTime $beforeDate = null, array $types = [Calendar::REGULAR]): array
	{
		$logger = $this->logger;
		try{
		$ids = array_map(function(?Ticker $t) use ($logger) {
			if (!isset($t) || $t == null) {
				$logger->error('DividendDataProvider::load()#'.__LINE__.' Ticker() is null');
			}

			return  $t->getId();
		}, $tickers);
		} catch(\Exception $e) {
			$logger->error($e->getMessage());
			$logger->error(print_r($tickers, true));
			throw $e;
		}

		return $this->mapByTicker(
				$this->diviRepo->findByTickerIds($ids, $afterDate, $beforeDate, $types)
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
