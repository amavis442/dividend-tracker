<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Position;

use App\Entity\Instrument;
use App\Entity\Position;
use App\Entity\Ticker;
use App\Entity\Transaction;
use App\Repository\CorporateActionRepository;
use App\Repository\TransactionRepository;
use App\Service\Position\WeightedAverage;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class WeightedAverageTest extends TestCase
{
    public function testTransactionsSetTheAmountOfAManualPosition(): void
    {
        $position = $this->position(null);

        $this->weightedAverage()->calc($position);

        self::assertSame(10.0, $position->getAmount());
        self::assertSame(100.0, $position->getAllocation());
    }

    public function testTheBrokerValuesOfASynchronisedPositionAreKept(): void
    {
        $position = $this->position($this->createStub(Instrument::class));

        $this->weightedAverage()->calc($position);

        self::assertSame(1140.11, $position->getAmount());
        self::assertSame(5000.0, $position->getAllocation());
    }

    private function position(?Instrument $instrument): Position
    {
        $position = new Position();
        $position->setTicker($this->createConfiguredStub(Ticker::class, ['getId' => 9]));
        $position->setInstrument($instrument);
        $position->setAmount(1140.11);
        $position->setAllocation(5000.0);

        return $position;
    }

    private function weightedAverage(): WeightedAverage
    {
        $buy = $this->createConfiguredStub(Transaction::class, [
            'getAmount' => 10.0,
            'getAllocation' => 100.0,
            'getPrice' => 10.0,
            'getProfit' => 0.0,
            'getSide' => Transaction::BUY,
            'getTransactionDate' => new \DateTime('2026-01-01'),
        ]);

        return new WeightedAverage(
            $this->createStub(EntityManagerInterface::class),
            $this->createConfiguredStub(TransactionRepository::class, ['findBy' => [$buy]]),
            $this->createConfiguredStub(CorporateActionRepository::class, ['findBy' => []])
        );
    }
}
