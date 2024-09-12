<?php

namespace App\DataFixtures;

use App\Entity\Tax;
use App\Factory\TaxFactory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use App\Factory\UserFactory;


class TaxFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        UserFactory::createMany(10);

        TaxFactory::createMany(5);
        /*
        DragonTreasureFactory::createMany(
            40,
            function () {
                return [
                    'owner' => UserFactory::random(),
                ];
            }
        );
        */

        $manager->flush();
    }
}
