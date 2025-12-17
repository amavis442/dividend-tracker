<?php

namespace App\Dto;

use App\Entity\Calendar;
use App\Entity\CorporateAction;
use App\Entity\Position;
use App\Entity\Trading212PieInstrument;
use App\Entity\Trading212PieMetaData;
use Doctrine\Common\Collections\Collection;

/**
 * DTO used by PieDataService.
 *
 * @property-read Collection<int, Trading212PieMetaData>   $metaData
 * @property-read Collection<int, Trading212PieInstrument> $instruments
 * @property-read Collection<int, Position>                 $positions
 * @property-read Collection<int, int>                      $tickerIds
 * @property-read Collection<int, CorporateAction>         $corporateActions
 * @property-read Collection<int, array<int, Calendar>>    $dividends
 *
 * @note The  first key is the TickerId and the key of each `$dividends` entry is the calendar ID.
 */
final class PieDataDto
{
    public function __construct(
        public readonly Collection $metaData,
        public readonly Collection $instruments,
        public readonly Collection $positions,
        public readonly Collection $tickerIds,
        public readonly Collection $corporateActions,
        public readonly Collection $dividends,
    ) {}
}
