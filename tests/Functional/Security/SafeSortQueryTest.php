<?php

declare(strict_types=1);

namespace App\Tests\Functional\Security;

use App\Entity\Ticker;
use App\Repository\DividendCalendarRepository;
use App\Repository\PaymentRepository;
use App\Repository\PositionRepository;
use App\Repository\ResearchRepository;
use App\Repository\TickerRepository;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\ResetDatabase;

#[Group('security')]
final class SafeSortQueryTest extends KernelTestCase
{
    use ResetDatabase;

    private const FIELD = 'id) DESC, (SELECT 1';
    private const DIRECTION = 'ASC, t.id';

    public function testUnknownValuesUseTheDefaultOrder(): void
    {
        $container = static::getContainer();

        self::assertStringEndsWith(
            'ORDER BY t.symbol ASC',
            $container->get(TickerRepository::class)->getAllQuery(self::FIELD, self::DIRECTION)->getDQL()
        );
        self::assertStringEndsWith(
            'ORDER BY r.id ASC',
            $container->get(ResearchRepository::class)->getForTickerQueryBuilder(
                new Ticker(),
                self::FIELD,
                self::DIRECTION
            )->getDQL()
        );
        self::assertStringContainsString(
            'ORDER BY p.payDate DESC',
            $container->get(PaymentRepository::class)->getAllQuery(self::FIELD, self::DIRECTION)->getDQL()
        );
        self::assertStringContainsString(
            'ORDER BY c.exDividendDate DESC',
            $container->get(DividendCalendarRepository::class)->getAllQuery(self::FIELD, self::DIRECTION)->getDQL()
        );
    }

    public function testLongTextColumnsAreNotSortable(): void
    {
        self::assertStringEndsWith(
            'ORDER BY t.symbol DESC',
            static::getContainer()->get(TickerRepository::class)->getAllQuery('description', 'desc')->getDQL()
        );
    }

    public function testTheSummaryOnlySortsOnGroupedValues(): void
    {
        $repository = static::getContainer()->get(PositionRepository::class);

        self::assertSame([], $repository->getSummary(self::FIELD, self::DIRECTION));
        self::assertSame([], $repository->getSummary('price', 'desc'));
        self::assertSame([], $repository->getSummary('sumDividend', 'desc'));
    }

    public function testKnownValuesAreKept(): void
    {
        self::assertStringEndsWith(
            'ORDER BY t.fullname DESC',
            static::getContainer()->get(TickerRepository::class)->getAllQuery('fullname', 'desc')->getDQL()
        );
    }
}
