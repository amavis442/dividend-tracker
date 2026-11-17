<?php

declare(strict_types=1);

namespace App\Decorator;

interface AdjustedDecoratorInterface
{
    public function getAdjustmentNote(): ?string;
}
