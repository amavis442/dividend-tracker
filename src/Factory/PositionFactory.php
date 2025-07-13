<?php

namespace App\Factory;

use App\Entity\Position;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<Position>
 */
final class PositionFactory extends PersistentProxyObjectFactory
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
        return Position::class;
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
            'closed' => self::faker()->boolean(),
            //'createdAt' => self::faker()->dateTime(),
            'currency' => CurrencyFactory::new(),
            'ignore_for_dividend' => self::faker()->boolean(),
            'price' => self::faker()->randomFloat(),
            'profit' => self::faker()->randomFloat(),
            'ticker' => TickerFactory::new(),
            'user' => UserFactory::new(),
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    protected function initialize(): static
    {
        return $this
            // ->afterInstantiate(function(Position $position): void {})
        ;
    }
}
