<?php

declare(strict_types=1);

namespace App\Exception;

use Symfony\Component\HttpKernel\Attribute\WithHttpStatus;

/**
 * No usable exchange rate is known, so amounts cannot be converted to euro.
 */
#[WithHttpStatus(503)]
final class ExchangeRateUnavailableException extends \RuntimeException
{
    /**
     * @param bool $providerFailed true when no rates could be fetched at all,
     *                             false when only this currency is missing
     */
    public function __construct(
        string $message,
        public readonly bool $providerFailed = false,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);
    }
}
