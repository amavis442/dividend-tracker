<?php

declare(strict_types=1);

namespace App\Dto;

use App\Entity\Ticker;
use App\Entity\Calendar;
use App\Entity\Transaction;
use App\Entity\Position;

final class BasicDataSetDto
{
    /**
     * @param array<int, Ticker> $tickers
     * @param array<int, array <int, Calendar>> $calendars
     * @param array<int, array <int, Transaction>> $transactions
     * @param array<int, Position> $positions
     * @param array<int, float> $exchangeRates
     * @param array<int, array <int, Calendar>> $paidCalendars full payment history, for the yield data
     *
     */
    public function __construct(
        public readonly array $tickers,
        public readonly array $calendars,
        public readonly array $transactions,
        public readonly array $positions,
        public readonly array $exchangeRates,
        public readonly array $paidCalendars = []
    ) {
    }
}
