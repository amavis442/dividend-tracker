<?php

declare(strict_types=1);

namespace App\Twig;

use DateTime;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Class TypeCastingExtension
 */
class MonthNumberToNameExtension extends AbstractExtension
{
    /**
     * @return array|\Twig_Filter[]
     */
    public function getFilters()
    {
        return [
            new TwigFilter('monthToName', function ($value) {
                $rawDate = date('Y') . '-' . $value . '-01';
                $formatDate = new DateTime($rawDate);

                return $formatDate->format('F');
            }),
        ];
    }
}
