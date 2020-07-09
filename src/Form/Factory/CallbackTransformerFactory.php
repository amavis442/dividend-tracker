<?php
namespace App\Form\Factory;

use Symfony\Component\Form\CallbackTransformer;

class CallbackTransformerFactory
{
    public static function create() : CallbackTransformer
    { 
        return new CallbackTransformer(
            function ($inputAsInt) {
                return $inputAsInt / 100;
            },
            function ($outputAsInt) {
                $a = $outputAsInt * 100; 
                return (int)floor($a);
            }
        );
    }
}
