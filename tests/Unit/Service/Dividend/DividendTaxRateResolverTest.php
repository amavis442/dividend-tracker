<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Dividend;

use App\Entity\Calendar;
use App\Entity\Currency;
use App\Entity\Ticker;
use App\Service\Dividend\DividendTaxRateResolver;
use App\Util\Constants;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

final class DividendTaxRateResolverTest extends TestCase
{
    #[TestWith(['GB', Constants::TAX_GB_RATE])]
    #[TestWith(['GBP', Constants::TAX_GB_RATE])]
    #[TestWith(['GBX', Constants::TAX_GB_RATE])]
    #[TestWith(['USD', Constants::DEFAULT_TAX_RATE])]
    public function testTheFallbackTaxFollowsTheCurrency(string $symbol, float $expected): void
    {
        $calendar = $this->createConfiguredStub(Calendar::class, [
            'getTicker' => $this->createConfiguredStub(Ticker::class, ['getTax' => null]),
            'getCurrency' => $this->createConfiguredStub(Currency::class, ['getSymbol' => $symbol]),
        ]);

        self::assertSame($expected, (new DividendTaxRateResolver())->getTaxRateForCalendar($calendar));
    }
}
