<?php
namespace App\Form\Factory;

use Symfony\Component\Form\CallbackTransformer;

class CallbackTransformerFactory
{
    public static function create() : CallbackTransformer
    { 
        return new CallbackTransformer(
            function ($inputAsFloat) {
                return round($inputAsFloat / 100, 2);
            },
            function ($outputAsInt) {
                return (int)($outputAsInt * 100);
            }
        );
    }
}