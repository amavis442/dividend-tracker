<?php

declare(strict_types=1);

namespace App\Form\DataTransformer;

use App\Entity\Ticker;
use App\Repository\TickerRepository;
use Symfony\Component\Form\DataTransformerInterface;

/**
 * Hidden ticker field: the form holds the ticker id, the model gets the Ticker.
 *
 * @implements DataTransformerInterface<int|null, int|null>
 */
final class TickerToIdTransformer implements DataTransformerInterface
{
    public function __construct(private readonly TickerRepository $tickerRepository)
    {
    }

    public function transform(mixed $value): ?int
    {
        return $value === null ? null : (int) $value;
    }

    public function reverseTransform(mixed $value): ?Ticker
    {
        if ($value === null || $value === '') {
            return null;
        }

        return $this->tickerRepository->find((int) $value);
    }
}
