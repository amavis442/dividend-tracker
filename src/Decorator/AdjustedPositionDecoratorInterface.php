<?php

declare(strict_types=1);

namespace App\Decorator;

interface AdjustedPositionDecoratorInterface extends AdjustedDecoratorInterface
{
    public function getAdjustedAmount(): float;
    public function getAdjustedAveragePrice(): float;
    public function getAdjustedAmountPerDate(
        \DateTimeInterface $datetime
    ): float;
    public function getAdjustedAveragePricePerDate(
        \DateTimeInterface $datetime
    ): float;
}
