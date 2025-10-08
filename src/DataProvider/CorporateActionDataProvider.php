<?php

namespace App\DataProvider;

use App\Repository\CorporateActionRepository;
use App\Entity\Ticker;

class CorporateActionDataProvider
{
	public function __construct(
		private CorporateActionRepository $actionRepo
	) {
	}

	/**
	 * Loads the data for an decorator
	 *
	 * @param Ticker[] $tickers
	 *
	 * @return array<int, array<int, \App\Entity\CorporateAction>>
	 */
	public function load(array $tickers): array
	{
		$ids = array_map(fn(Ticker $t) => $t->getId(), $tickers);

		return  $this->mapByPosition(
				$this->actionRepo->findByTickerIds($ids)
		);
	}

	private function mapByPosition(array $corporateActions): array
	{
		$map = [];
		foreach ($corporateActions as $corporateAction) {
			$id = $corporateAction->getTicker()->getId();
			$map[$id][] = $corporateAction;
		}
		return $map;
	}
}
