<?php

declare(strict_types=1);

namespace App\Form\Factory;

use Symfony\Component\Form\CallbackTransformer;

class CallbackTransformerValutaFactory
{
    public static function create(): CallbackTransformer
    {
        return new CallbackTransformer(
            function ($inputAsInt): int|float {
                return $inputAsInt / 1000;
            },
            function ($outputAsInt): int {
                $a = $outputAsInt * 1000;
                return (int)floor($a);
            }
        );
    }
}
