<?php

namespace App\Factory;

use App\Entity\Tax;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<Tax>
 */
final class TaxFactory extends PersistentProxyObjectFactory
{
    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#factories-as-services
     */
    public function __construct() {}

    public static function class(): string
    {
        return Tax::class;
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     *
     */
    protected function defaults(): array
    {
        $faker = \Faker\Factory::create();

        return [
            'taxRate' => $faker->numberBetween(10, 30),
            'validFrom' => $faker->dateTime(),
            'createdAt' => $faker->dateTime(),
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    protected function initialize(): static
    {
        return $this;
    }
}
