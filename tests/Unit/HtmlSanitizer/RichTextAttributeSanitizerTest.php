<?php

declare(strict_types=1);

namespace App\Tests\Unit\HtmlSanitizer;

use App\HtmlSanitizer\RichTextAttributeSanitizer;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

final class RichTextAttributeSanitizerTest extends TestCase
{
    #[TestWith(['font-size:18px', 'font-size:18px'])]
    #[TestWith(['color: red; text-align: center', 'color:red;text-align:center'])]
    #[TestWith(['position:fixed;inset:0;z-index:9999', null])]
    #[TestWith(['color:red;position:fixed', 'color:red'])]
    #[TestWith(['background-color:url(javascript:alert(1))', null])]
    #[TestWith(['width:expression(alert(1))', null])]
    public function testOnlyEditorStylesRemain(string $style, ?string $expected): void
    {
        self::assertSame($expected, $this->sanitize('style', $style));
    }

    #[TestWith(['marker-yellow', 'marker-yellow'])]
    #[TestWith(['image image_resized image-style-side', 'image image_resized image-style-side'])]
    #[TestWith(['fixed inset-0 z-50', null])]
    #[TestWith(['table fixed', 'table'])]
    public function testOnlyEditorClassesRemain(string $classes, ?string $expected): void
    {
        self::assertSame($expected, $this->sanitize('class', $classes));
    }

    private function sanitize(string $attribute, string $value): ?string
    {
        return (new RichTextAttributeSanitizer())->sanitizeAttribute('span', $attribute, $value, new HtmlSanitizerConfig());
    }
}
