<?php

declare(strict_types=1);

namespace App\Validator;

use Attribute;
use Symfony\Component\Validator\Constraints\Compound;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\PasswordStrength;

/**
 * Password rules for every path that sets a password: registration, the
 * account page, POST /api/users and app:create-user.
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_METHOD)]
final class StrongPassword extends Compound
{
    public const MIN_LENGTH = 12;
    // Symfony's limit for password hashing input.
    public const MAX_LENGTH = 4096;

    protected function getConstraints(array $options): array
    {
        return [
            new NotBlank(message: 'Please enter a password'),
            new Length(
                min: self::MIN_LENGTH,
                max: self::MAX_LENGTH,
                minMessage: 'Your password should be at least {{ limit }} characters',
            ),
            new PasswordStrength(minScore: PasswordStrength::STRENGTH_MEDIUM),
        ];
    }
}
