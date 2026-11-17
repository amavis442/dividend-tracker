<?php

namespace App\Factory;

use Faker\Generator;
use Faker\Factory;
use App\Factory\Provider\ISINProvider;

final class CustomGenerator
{
    public static function faker(): Generator
    {
        $faker = Factory::create();
        $faker->addProvider(new ISINProvider($faker));
        return $faker;
    }
}
