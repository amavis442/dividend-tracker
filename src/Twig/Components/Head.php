<?php

namespace App\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
final class Head
{
    public string $tag = 'h1';
    public string $variant = 'h1';

    //text-2xl md:text-3xl lg:text-5xl

    public function getVariantClasses(): string
    {
        return match ($this->tag) {
            'h1' => 'text-5xl',
            'h2' => 'text-4xl',
            'h3' => 'text-3xl',
            'h4' => 'text-2xl',
            'h5' => 'text-xl',
            'h6' => 'text-lg',
            default => throw new \LogicException(sprintf('Unknown button type "%s"', $this->tag)),
        };
    }

    public function setVariant($variant)
    {
        $this->variant = $variant;
        $this->tag = $variant;
    }
}
