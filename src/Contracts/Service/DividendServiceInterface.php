<?php

namespace App\Contracts\Service;

use App\Entity\Calendar;
use App\Entity\Position;
use App\Entity\Ticker;
use Doctrine\Common\Collections\Collection;

interface DividendServiceInterface
{
    public function getExchangeRate(Calendar $calendar): ?float;
    public function getTaxRate(Calendar $calendar): ?float;
    public function getExchangeAndTax(Position $position, Calendar $calendar): array;
    public function getPositionSize(Collection $transactions, Calendar $calendar): ?float;
    public function getRegularCalendar(Ticker $ticker): ?Calendar;
    public function getPositionAmount(Calendar $calendar): ?float;
    public function getNetDividend(Position $position, Calendar $calendar): ?float;
    public function getTotalNetDividend(Calendar $calendar): ?float;
    public function getCashAmount(Ticker $ticker): ?float;
    public function getForwardNetDividend(Position $position): ?float;
    public function getForwardNetDividendYield(Position $position): ?float;
    public function getNetDividendPerShare(?Position $position): ?float;
    public function setCummulateDividendAmount(bool $cummulateDividendAmount = true): DividendServiceInterface;
}
