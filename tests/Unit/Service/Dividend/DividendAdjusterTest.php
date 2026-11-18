<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Dividend;

use App\Entity\Calendar;
use App\Entity\CorporateAction;
use App\Service\Dividend\DividendAdjuster;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class DividendAdjusterTest extends TestCase
{
    /**
     * @return iterable<string, array{string, string, float}>
     */
    public static function dividends(): iterable
    {
        yield 'recorded before the split' => ['2026-03-01', '2026-05-01', 2.0];
        yield 'imported later but ex-dividend before the split' => ['2026-05-01', '2026-03-01', 2.0];
        yield 'recorded and ex-dividend after the split' => ['2026-05-01', '2026-05-15', 1.0];
    }

    #[DataProvider('dividends')]
    public function testADividendFollowsTheSplit(string $createdAt, string $exDividendDate, float $expected): void
    {
        $calendar = $this->calendar($createdAt, $exDividendDate);

        self::assertSame($expected, (new DividendAdjuster())->getAdjustedDividend($calendar, [$this->split(0.5)]));
    }

    public function testAFutureSplitIsIgnored(): void
    {
        $split = $this->createConfiguredStub(CorporateAction::class, [
            'getEventDate' => new \DateTime('+1 month'),
            'getRatio' => 0.5,
        ]);

        self::assertSame(
            1.0,
            (new DividendAdjuster())->getAdjustedDividend($this->calendar('2026-03-01', '2026-03-01'), [$split])
        );
    }

    public function testAnInvalidRatioIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        (new DividendAdjuster())->getAdjustedDividend($this->calendar('2026-03-01', '2026-03-01'), [$this->split(0.0)]);
    }

    private function calendar(string $createdAt, string $exDividendDate): Calendar
    {
        return $this->createConfiguredStub(Calendar::class, [
            'getCashAmount' => 1.0,
            'getCreatedAt' => new \DateTimeImmutable($createdAt),
            'getExDividendDate' => new \DateTime($exDividendDate),
        ]);
    }

    private function split(float $ratio): CorporateAction
    {
        return $this->createConfiguredStub(CorporateAction::class, [
            'getEventDate' => new \DateTime('2026-04-01'),
            'getRatio' => $ratio,
        ]);
    }
}
