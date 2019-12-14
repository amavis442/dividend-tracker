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
     * @return array|\Twig_Filter[]
     */
    public function getFilters()
    {
        return [
            new TwigFilter('int', function ($value) {
                return (int) $value;
            }),
            new TwigFilter('float', function ($value) {
                return (float) $value;
            }),
            new TwigFilter('string', function ($value) {
                return (string) $value;
            }),
            new TwigFilter('bool', function ($value) {
                return (bool) $value;
            }),
            new TwigFilter('array', function (object $value) {
                return (array) $value;
            }),
            new TwigFilter('object', function (array $value) {
                return (object) $value;
            }),
        ];
    }
}