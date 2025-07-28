<?php

namespace App\Factory;

use App\Entity\Ticker;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<Ticker>
 */
final class TickerFactory extends PersistentProxyObjectFactory
{
    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#factories-as-services
     *
     */
    public function __construct() {}

    public static function class(): string
    {
        return Ticker::class;
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     *
     */
    protected function defaults(): array
    {
        // @phpstan-ignore method.notFound
        $isin = self::faker()->isin();

        return [
            'branch' => BranchFactory::new(),
            'fullname' => self::faker()->text(255),
            'isin' => $isin,
            'symbol' => self::faker()->text(255),
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    protected function initialize(): static
    {
        return $this
            // ->afterInstantiate(function(Ticker $ticker): void {})
        ;
    }
}
