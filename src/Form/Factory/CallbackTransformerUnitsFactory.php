<?php

declare(strict_types=1);

namespace App\Form\Factory;

use Symfony\Component\Form\CallbackTransformer;

class CallbackTransformerUnitsFactory
{
    public static function create(): CallbackTransformer
    {
        return new CallbackTransformer(
            function ($inputAsInt): int|float {
                return $inputAsInt / 10000000;
            },
            function ($outputAsInt): int {
                $a = $outputAsInt * 10000000;
                return (int)floor($a);
            }
        );
    }
}
