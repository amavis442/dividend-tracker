<?php

declare(strict_types=1);

namespace App\Doctrine\Type;

use App\Security\SecretBox;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\StringType;
use LogicException;

/**
 * A string column that is encrypted in the database and plain text in PHP.
 * Doctrine creates types itself, so the SecretBox is set when the kernel boots.
 */
final class EncryptedStringType extends StringType
{
    public const NAME = 'encrypted_string';

    private static ?SecretBox $secretBox = null;

    public static function setSecretBox(SecretBox $secretBox): void
    {
        self::$secretBox = $secretBox;
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        return $value === null ? null : self::secretBox()->encrypt((string) $value);
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?string
    {
        return $value === null ? null : self::secretBox()->decrypt((string) $value);
    }

    private static function secretBox(): SecretBox
    {
        return self::$secretBox ?? throw new LogicException('EncryptedStringType is used before the kernel booted.');
    }
}
