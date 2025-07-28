<?php

namespace App\Domain\ValueObject;

use Brick\Money\Money as BrickMoney;
use Brick\Math\RoundingMode;

// Use Brick\Money\Money for tax and exchange
final class Money
{
    private BrickMoney $money;

    public function __construct(string $amount, string $currency)
    {
        $this->money = BrickMoney::of($amount, $currency);
    }

    public function multiply(string $factor): Money
    {
        return new self(
            $this->money->multipliedBy($factor, RoundingMode::HALF_UP)->getAmount(),
            $this->money->getCurrency()->getCurrencyCode()
        );
    }

    public function applyTax(string $taxRate): Money
    {
        $rate = BrickMoney::of('1', $this->money->getCurrency()->getCurrencyCode())
                         ->minus($taxRate);
        return $this->multiply((string) $rate->getAmount());
    }

    public function convert(string $exchangeRate, string $newCurrency): Money
    {
        $converted = $this->money->multipliedBy($exchangeRate, RoundingMode::HALF_UP);
        return new self((string) $converted->getAmount(), $newCurrency);
    }

    public function getAmount(): string
    {
        return (string) $this->money->getAmount();
    }

    public function getCurrency(): string
    {
        return $this->money->getCurrency()->getCurrencyCode();
    }
}
