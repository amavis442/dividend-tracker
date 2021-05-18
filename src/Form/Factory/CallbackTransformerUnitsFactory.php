<?php

namespace App\Form\Factory;

use Symfony\Component\Form\CallbackTransformer;

class CallbackTransformerUnitsFactory
{
    public static function create(): CallbackTransformer
    {
        return new CallbackTransformer(
            function ($inputAsInt) {
                return $inputAsInt / 10000000;
            },
            function ($outputAsInt) {
                $a = $outputAsInt * 10000000;
                return (int)floor($a);
            }
        );
    }
}
