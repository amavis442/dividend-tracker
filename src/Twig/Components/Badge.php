<?php

namespace App\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
final class Badge
{
    public string $variant = 'default';
    public string $type = 'default';

    public function getVariantClasses(): string
    {
        return match ($this->variant) {
            'default' => 'text-white bg-blue-500 hover:bg-blue-700',
            'success' => 'text-white bg-green-600 hover:bg-green-700',
            'primary' => 'text-blue-900 bg-blue-300 hover:bg-blue-300',
            'secondary' => 'text-white bg-slate-500 hover:bg-slate-700',
            'danger' => 'text-white bg-red-600 hover:bg-red-700 focus:ring-4 focus:ring-red-300 focus:outline-none',
            'info' =>  'text-white bg-orange-400 hover:bg-orange-400',
            default => throw new \LogicException(sprintf('Unknown badge variant "%s"', $this->variant)),
        };
    }

    public function getTypeClasses(): string
    {
        return match($this->type)
        {
            'default' => 'rounded-md',
            'pill' => 'p-2 h-4 inline-flex items-center justify-center rounded-full',
            default => throw new \LogicException(sprintf('Unknown badge type "%s"', $this->type)),
        };
    }
}
