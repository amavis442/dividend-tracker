<?php

namespace App\Factory;

use App\Entity\Tax;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

use function Zenstruck\Foundry\faker;

/**
 * @extends PersistentProxyObjectFactory<User>
 */
final class TaxFactory extends PersistentProxyObjectFactory
{
    private const USERNAMES = [
        'FlamingInferno',
        'ScaleSorcerer',
        'TheDragonWithBadBreath',
        'BurnedOut',
        'ForgotMyOwnName',
        'ClumsyClaws',
        'HoarderOfUselessTrinkets',
    ];

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#factories-as-services
     *
     * @todo inject services if required
     */
    public function __construct(private UserPasswordHasherInterface $passwordHasher) {}

    public static function class(): string
    {
        return Tax::class;
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     *
     * @todo add your default values here
     */
    protected function defaults(): array|callable
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
        return $this->afterInstantiate(function (Tax $tax): void {})
            // ->afterInstantiate(function(User $user): void {})
        ;
    }
}
