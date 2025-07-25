<?php

namespace App\Factory;

use App\Factory\Provider\ISINProvider;

final class CustomGenerator
{
    public static function faker(): \Faker\Generator
    {
        $faker = \Faker\Factory::create();
        $faker->addProvider(new ISINProvider($faker));
        return $faker;
    }
}

