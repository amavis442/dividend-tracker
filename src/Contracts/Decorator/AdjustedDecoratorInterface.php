<?php

declare(strict_types=1);

namespace App\Contracts\Decorator;

interface AdjustedDecoratorInterface
{
    public function getAdjustmentNote(): ?string;
}
