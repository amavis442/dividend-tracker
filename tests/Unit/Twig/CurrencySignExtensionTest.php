<?php

declare(strict_types=1);

namespace App\Tests\Unit\Twig;

use App\Twig\CurrencySignExtension;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

final class CurrencySignExtensionTest extends TestCase
{
    #[TestWith(['&pound;', '£'])]
    #[TestWith(['&euro;', '€'])]
    #[TestWith(['$', '$'])]
    #[TestWith([null, ''])]
    #[TestWith(['<img src=x onerror=alert(1)>', '&lt;img src=x onerror=alert(1)&gt;'])]
    #[TestWith(['&lt;script&gt;', '&lt;script&gt;'])]
    public function testSignsArePrintedSafely(?string $sign, string $expected): void
    {
        self::assertSame($expected, (new CurrencySignExtension())->currencySign($sign));
    }
}
