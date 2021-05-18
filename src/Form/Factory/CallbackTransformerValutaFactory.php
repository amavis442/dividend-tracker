<?php

namespace App\Form\Factory;

use Symfony\Component\Form\CallbackTransformer;

class CallbackTransformerValutaFactory
{
    public static function create(): CallbackTransformer
    {
        return new CallbackTransformer(
            function ($inputAsInt) {
                return $inputAsInt / 1000;
            },
            function ($outputAsInt) {
                $a = $outputAsInt * 1000;
                return (int)floor($a);
            }
        );
    }
}
