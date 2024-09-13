<?php

namespace App\Factory;

use App\Entity\Calendar;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<Calendar>
 */
final class CalendarFactory extends PersistentProxyObjectFactory
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
        return Calendar::class;
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     *
     * @todo add your default values here
     */
    protected function defaults(): array|callable
    {
        return [
            'cashAmount' => self::faker()->randomFloat(),
            'createdAt' => self::faker()->dateTime(),
            'exDividendDate' => self::faker()->dateTime(),
            'paymentDate' => self::faker()->dateTime(),
            'recordDate' => self::faker()->dateTime(),
            'ticker' => TickerFactory::new(),
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    protected function initialize(): static
    {
        return $this
            // ->afterInstantiate(function(Calendar $calendar): void {})
        ;
    }
}
