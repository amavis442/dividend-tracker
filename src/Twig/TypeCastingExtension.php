<?php

declare(strict_types=1);

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Class TypeCastingExtension
 */
class TypeCastingExtension extends AbstractExtension
{
    /**
     * Summary of getFilters
     * @return array
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('int', function ($value): int {
                return (int) $value;
            }),
            new TwigFilter('float', function ($value): float {
                return (float) $value;
            }),
            new TwigFilter('string', function ($value): string {
                return (string) $value;
            }),
            new TwigFilter('bool', function ($value): bool {
                return (bool) $value;
            }),
            new TwigFilter('array', function (object $value): array {
                return (array) $value;
            }),
            new TwigFilter('object', function (array $value): \stdClass {
                return (object) $value;
            }),
        ];
    }
}
