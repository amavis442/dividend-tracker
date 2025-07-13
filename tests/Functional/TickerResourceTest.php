<?php

namespace App\Tests\Functional;

use App\Factory\TaxFactory;
use App\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Test\ResetDatabase;
use App\Repository\UserRepository;
use Zenstruck\Foundry\Test\Factories;

/**
 * Remember that the Dama bundle restores database in original
 * form. So you will not see any data in the database tables.
 * This is kinda pretty cool.
 */
class TickerResourceTest extends WebTestCase
{
    use HasBrowser;
    use Factories;
    use ResetDatabase;

    public function testGetCollectionTickers(): void
    {
        UserFactory::createOne(["email" => "test@test.nl"]);
        TaxFactory::createMany(5);

        $userRepository = static::getContainer()->get(UserRepository::class);
        $testUser = $userRepository->findOneByEmail("test@test.nl");

        $this->browser()
            ->actingAs($testUser)
            ->get("/api/taxes")
            ->assertJson()
            ->assertJsonMatches('"hydra:totalItems"', 5);
    }
}
