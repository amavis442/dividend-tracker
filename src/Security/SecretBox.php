<?php

declare(strict_types=1);

namespace App\Security;

use RuntimeException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Encrypts secrets (such as broker API keys) before they are stored, with
 * libsodium's secretbox (XSalsa20-Poly1305) and the APP_ENCRYPTION_KEY.
 *
 * Stored values start with a version prefix. Values without it are treated as
 * plain text from before encryption, so existing rows keep working until
 * `app:api-keys:encrypt` has converted them.
 */
final class SecretBox
{
    public const PREFIX = 'enc:v1:';

    public function __construct(
        #[Autowire('%env(APP_ENCRYPTION_KEY)%')] private readonly string $encodedKey,
    ) {
    }

    public static function isEncrypted(string $value): bool
    {
        return str_starts_with($value, self::PREFIX);
    }

    public function encrypt(string $plainText): string
    {
        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);

        return self::PREFIX . base64_encode($nonce . sodium_crypto_secretbox($plainText, $nonce, $this->key()));
    }

    public function decrypt(string $stored): string
    {
        if (!self::isEncrypted($stored)) {
            return $stored;
        }

        $raw = base64_decode(substr($stored, strlen(self::PREFIX)), true);
        if ($raw === false || strlen($raw) <= SODIUM_CRYPTO_SECRETBOX_NONCEBYTES) {
            throw new RuntimeException('Encrypted value is malformed.');
        }

        $plainText = sodium_crypto_secretbox_open(
            substr($raw, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES),
            substr($raw, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES),
            $this->key()
        );
        if ($plainText === false) {
            throw new RuntimeException('Encrypted value cannot be decrypted with the current APP_ENCRYPTION_KEY.');
        }

        return $plainText;
    }

    private function key(): string
    {
        $key = base64_decode($this->encodedKey, true);
        if ($key === false || strlen($key) !== SODIUM_CRYPTO_SECRETBOX_KEYBYTES) {
            throw new RuntimeException(
                'APP_ENCRYPTION_KEY must be a base64 encoded 32-byte key. Generate one with: '
                . "php -r 'echo base64_encode(random_bytes(32)), PHP_EOL;'"
            );
        }

        return $key;
    }
}
