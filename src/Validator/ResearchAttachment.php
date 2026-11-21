<?php

declare(strict_types=1);

namespace App\Validator;

use Attribute;
use Symfony\Component\Validator\Constraints\Compound;
use Symfony\Component\Validator\Constraints\File;

/**
 * Rules for files attached to research: a size limit and a whitelist of
 * document and image types. Both the extension and the detected MIME type
 * must match an entry in the list.
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_METHOD)]
final class ResearchAttachment extends Compound
{
    public const MAX_SIZE = '10M';

    /** Extension => allowed MIME types. */
    public const ALLOWED_TYPES = [
        'pdf' => ['application/pdf'],
        'png' => ['image/png'],
        'jpg' => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'gif' => ['image/gif'],
        'webp' => ['image/webp'],
        'txt' => ['text/plain'],
        'csv' => ['text/csv', 'text/plain'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
        'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        'odt' => ['application/vnd.oasis.opendocument.text'],
        'ods' => ['application/vnd.oasis.opendocument.spreadsheet'],
    ];

    /** Types a browser may show directly; everything else is downloaded. */
    public const INLINE_MIME_TYPES = ['application/pdf', 'image/png', 'image/jpeg', 'image/gif', 'image/webp'];

    protected function getConstraints(array $options): array
    {
        return [
            new File(
                maxSize: self::MAX_SIZE,
                extensions: self::ALLOWED_TYPES,
                extensionsMessage: 'This file type is not allowed. Allowed types: {{ extensions }}.',
            ),
        ];
    }
}
