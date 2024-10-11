<?php

declare(strict_types=1);

namespace App\Twig;

use DateTime;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class TypeCastingExtension
 */
class MonthNumberToNameExtension extends AbstractExtension
{
    public function __construct(private TranslatorInterface $translator)
    {

    }

    /**
     * Summary of getFilters
     * @return array
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('monthToName', function ($value) {
                $rawDate = date('Y') . '-' . $value . '-01';
                $formatDate = new DateTime($rawDate);

                return $this->translator->trans($formatDate->format('F'));
            }),
        ];
    }
}
