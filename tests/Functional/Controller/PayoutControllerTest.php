<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Entity\User;
use App\Factory\UserFactory;
use App\Repository\UserRepository;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

#[Group('controller')]
final class PayoutControllerTest extends WebTestCase
{
    use Factories;
    use ResetDatabase;

    public function testPaymentsReportRendersWithoutData(): void
    {
        $client = static::createClient();

        $email = UserFactory::createOne()->getEmail();
        $user = static::getContainer()->get(UserRepository::class)->findOneBy(['email' => $email]);
        self::assertInstanceOf(User::class, $user);
        $client->loginUser($user);

        $client->request('GET', '/nl/dashboard/report/payments');

        self::assertResponseIsSuccessful();
    }
}
