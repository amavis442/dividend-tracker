<?php

namespace App\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
final class Alert
{
    public string $variant = 'default';

    public function getVariantClasses(): string
    {
        return match ($this->variant) {
            'default' => 'text-white bg-blue-500 hover:bg-blue-700',
            'success' => 'text-white bg-green-600 hover:bg-green-700',
            'secondary' => 'text-white bg-slate-500 hover:bg-slate-700',
            'danger' => 'text-white bg-red-600 hover:bg-red-700 focus:ring-4 focus:ring-red-300 focus:outline-none',
            'info' =>  'text-white bg-orange-500 hover:bg-orange-700',
            default => throw new \LogicException(sprintf('Unknown button type "%s"', $this->variant)),
        };
    }
}
