<?php

namespace App\Factory;

use App\Entity\DividendTracker;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<DividendTracker>
 */
final class DividendTrackerFactory extends PersistentProxyObjectFactory
{
    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#factories-as-services
     *
     */
    public function __construct()
    {
    }

    public static function class(): string
    {
        return DividendTracker::class;
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     *
     */
    protected function defaults(): array
    {
        return [
            'createdAt' => self::faker()->dateTime(),
            'dividend' => self::faker()->randomFloat(),
            'principle' => self::faker()->randomFloat(),
            'sampleDate' => self::faker()->dateTime(),
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    protected function initialize(): static
    {
        return $this
            // ->afterInstantiate(function(DividendTracker $dividendTracker): void {})
        ;
    }
}
