<?php

declare(strict_types=1);

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Prints a currency sign safely. Signs are stored as HTML entities such as
 * `&pound;`; they are decoded and escaped, so markup in a sign is never run.
 */
final class CurrencySignExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('currency_sign', [$this, 'currencySign'], ['is_safe' => ['html']]),
        ];
    }

    public function currencySign(?string $sign): string
    {
        $decoded = html_entity_decode((string) $sign, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return htmlspecialchars($decoded, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
    }
}
