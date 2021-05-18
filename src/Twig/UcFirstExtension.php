<?php

declare(strict_types=1);

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\Markup;
use Twig\TwigFilter;

class UcFirstExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [new TwigFilter('ucfirst', [$this, 'ucFirstFilter'])];
    }

    public function ucFirstFilter(string $val): Markup
    {
        return new Markup(ucfirst($val), 'UTF-8');
    }
}
