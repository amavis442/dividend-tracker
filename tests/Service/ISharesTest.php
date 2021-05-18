<?php

namespace App\Tests\Service;

use App\Service\ISharesService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ISharesTest extends KernelTestCase
{
    public function testSomething(): void
    {
        $kernel = self::bootKernel();
        $container = self::$container;

        $iSharesService = $container->get(ISharesService::class);
        $content = $iSharesService->getLatest('SEMB');

        $this->assertSame('test', $kernel->getEnvironment());
        // /self::$container->get(ISharesService::class);
        //$routerService = self::$container->get('router');
        //$myCustomService = self::$container->get(CustomService::class);
    }
}
