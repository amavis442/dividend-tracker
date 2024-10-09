<?php

namespace App\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Traversable;

#[AsTwigComponent]
final class ListGroup
{
    public Traversable $list_items;
    
}
