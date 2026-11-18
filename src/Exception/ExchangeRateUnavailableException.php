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
}
