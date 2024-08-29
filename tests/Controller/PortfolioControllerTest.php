<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class PortfolioControllerTest extends WebTestCase
{
    public function testHomePage(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', 'http://dividend.local/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Dividend tracker');
    }
}
