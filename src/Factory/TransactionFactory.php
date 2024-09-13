<?php

namespace App\Factory;

use App\Entity\Transaction;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<Transaction>
 */
final class TransactionFactory extends PersistentProxyObjectFactory
{
    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#factories-as-services
     *
     * @todo inject services if required
     */
    public function __construct()
    {
    }

    public static function class(): string
    {
        return Transaction::class;
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     *
     * @todo add your default values here
     */
    protected function defaults(): array|callable
    {
        return [
            'allocation' => self::faker()->randomFloat(),
            'amount' => self::faker()->randomFloat(),
            'avgprice' => self::faker()->randomFloat(),
            'createdAt' => self::faker()->dateTime(),
            'currency' => CurrencyFactory::new(),
            'exchangeRate' => self::faker()->randomFloat(),
            'finraFee' => self::faker()->randomFloat(),
            'fx_fee' => self::faker()->randomFloat(),
            'originalPrice' => self::faker()->randomFloat(),
            'price' => self::faker()->randomFloat(),
            'profit' => self::faker()->randomFloat(),
            'side' => self::faker()->randomNumber(),
            'stampduty' => self::faker()->randomFloat(),
            'total' => self::faker()->randomFloat(),
            'transactionDate' => self::faker()->dateTime(),
            'transactionFee' => self::faker()->randomFloat(),
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    protected function initialize(): static
    {
        return $this
            // ->afterInstantiate(function(Transaction $transaction): void {})
        ;
    }
}
