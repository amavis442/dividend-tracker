<?php

declare(strict_types=1);

namespace App\HtmlSanitizer;

use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;
use Symfony\Component\HtmlSanitizer\Visitor\AttributeSanitizer\AttributeSanitizerInterface;

/**
 * Keeps only the styles and classes the rich text editor produces, so shared
 * texts cannot lay themselves over the page (for example with position:fixed).
 */
final class RichTextAttributeSanitizer implements AttributeSanitizerInterface
{
    private const STYLE_PROPERTIES = [
        'background-color', 'box-sizing', 'color', 'float', 'font-family', 'font-size', 'font-style',
        'font-weight', 'height', 'line-height', 'margin-left', 'margin-right', 'text-align',
        'text-decoration', 'vertical-align', 'width',
    ];

    private const CLASS_PATTERN = '/^(image|image_resized|image-style-[a-z-]+|marker-[a-z]+|pen-[a-z]+'
        . '|table|text-(tiny|small|big|huge)|todo-list)$/';

    public function getSupportedElements(): ?array
    {
        return null;
    }

    /**
     * @return list<string>
     */
    public function getSupportedAttributes(): array
    {
        return ['style', 'class'];
    }

    public function sanitizeAttribute(
        string $element,
        string $attribute,
        string $value,
        HtmlSanitizerConfig $config
    ): ?string {
        $clean = $attribute === 'style' ? $this->style($value) : $this->classes($value);

        return $clean === '' ? null : $clean;
    }

    private function style(string $value): string
    {
        $declarations = [];
        foreach (explode(';', $value) as $declaration) {
            [$property, $propertyValue] = array_map('trim', explode(':', $declaration, 2)) + [1 => ''];
            $property = strtolower($property);
            if (
                !in_array($property, self::STYLE_PROPERTIES, true)
                || $propertyValue === ''
                || preg_match('/url\s*\(|expression\s*\(|javascript:|[<>\\\\]/i', $propertyValue) === 1
            ) {
                continue;
            }
            $declarations[] = $property . ':' . $propertyValue;
        }

        return implode(';', $declarations);
    }

    private function classes(string $value): string
    {
        $classes = preg_split('/\s+/', trim($value)) ?: [];

        return implode(' ', array_filter(
            $classes,
            static fn(string $class): bool => preg_match(self::CLASS_PATTERN, $class) === 1
        ));
    }
}
