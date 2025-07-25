<?php

namespace App\Tests\Unit\Service\DividendDate;

use App\Service\DividendDate\ISharesService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ISharesTest extends KernelTestCase
{

    public function testGetDataFromServiceWithTicker(): void
    {
        self::bootKernel();

        /* Use this for CI
        self::bootKernel([
        'environment' => 'my_test_env',
        'debug'       => false,
        ]);
         */

        $container = static::getContainer();

        $iSharesService = $container->get(ISharesService::class);
        $content = $iSharesService->getData('SEMB','');

        $this->assertNotEmpty($content, "Not received any data");
        $this->assertArrayHasKey('ExDate', $content[0]);

        //$this->assertSame('test', $kernel->getEnvironment());
    }
}
