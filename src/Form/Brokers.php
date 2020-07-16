<?php

namespace App\Form;

class Brokers
{
    public static function list()
    {
        return [
            'Trading212' => 'Trading212',
            'Flatex' =>  'Flatex',
            'eToro' =>  'eToro',
        ];
    }
}
