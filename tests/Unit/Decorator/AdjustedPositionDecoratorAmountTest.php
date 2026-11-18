<?php

declare(strict_types=1);

namespace App\Tests\Unit\Decorator;

use App\Decorator\AdjustedPositionDecorator;
use App\Entity\CorporateAction;
use App\Entity\Instrument;
use App\Entity\Position;
use App\Entity\Transaction;
use App\Service\Transaction\TransactionAdjuster;
use PHPUnit\Framework\TestCase;

final class AdjustedPositionDecoratorAmountTest extends TestCase
{
    public function testTransactionsGiveTheAmountAfterASplit(): void
    {
        $position = $this->createConfiguredStub(Position::class, ['getAmount' => 5700.0]);

        self::assertSame(1140.0, $this->decorator($position, [$this->buy(5700.0)])->getAdjustedAmount());
    }

    public function testAFutureSplitDoesNotChangeTheAmount(): void
    {
        $position = $this->createConfiguredStub(Position::class, ['getAmount' => 5700.0]);
        $split = $this->createConfiguredStub(CorporateAction::class, [
            'getEventDate' => new \DateTime('+1 month'),
            'getRatio' => 0.2,
        ]);
        $decorator = new AdjustedPositionDecorator(
            $position,
            [$this->buy(5700.0)],
            [$split],
            new TransactionAdjuster()
        );

        self::assertSame(5700.0, $decorator->getAdjustedAmount());
    }

    public function testTheBrokerAmountLeadsForASynchronisedPosition(): void
    {
        $position = $this->createConfiguredStub(Position::class, [
            'getAmount' => 1140.11,
            'getInstrument' => $this->createStub(Instrument::class),
        ]);

        self::assertSame(1140.11, $this->decorator($position, [$this->buy(10.0)])->getAdjustedAmount());
    }

    public function testThePositionAmountIsUsedPerDateWithoutTransactions(): void
    {
        $position = $this->createConfiguredStub(Position::class, ['getAmount' => 25.0]);

        self::assertSame(
            25.0,
            $this->decorator($position, [])->getAdjustedAmountPerDate(new \DateTime('2026-05-01'))
        );
    }

    public function testAFutureSplitIsNotMentionedInTheNote(): void
    {
        $position = $this->createStub(Position::class);
        $split = $this->createConfiguredStub(CorporateAction::class, [
            'getEventDate' => new \DateTime('+1 month'),
            'getRatio' => 0.2,
        ]);
        $decorator = new AdjustedPositionDecorator($position, [], [$split], new TransactionAdjuster());

        self::assertNull($decorator->getAdjustmentNote());
    }

    public function testThePositionAmountIsUsedWithoutTransactions(): void
    {
        $position = $this->createConfiguredStub(Position::class, ['getAmount' => 25.0]);

        self::assertSame(25.0, $this->decorator($position, [])->getAdjustedAmount());
    }

    /**
     * @param Transaction[] $transactions
     */
    private function decorator(Position $position, array $transactions): AdjustedPositionDecorator
    {
        $split = $this->createConfiguredStub(CorporateAction::class, [
            'getEventDate' => new \DateTime('2026-04-01'),
            'getRatio' => 0.2,
        ]);

        return new AdjustedPositionDecorator($position, $transactions, [$split], new TransactionAdjuster());
    }

    private function buy(float $amount): Transaction
    {
        return $this->createConfiguredStub(Transaction::class, [
            'getAmount' => $amount,
            'getSide' => Transaction::BUY,
            'getTransactionDate' => new \DateTime('2026-01-01'),
        ]);
    }
}
