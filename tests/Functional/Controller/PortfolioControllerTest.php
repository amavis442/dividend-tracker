<?php

namespace App\Tests\Functional\Controller;

use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

#[Group('controller')]
class PortfolioControllerTest extends WebTestCase
{
    use Factories;
	use ResetDatabase;

    public function testHomePage(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/nl/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Dividend tracker');
    }
}
