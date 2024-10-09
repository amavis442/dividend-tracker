<?php

namespace App\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
final class ListGroupItem
{
    public bool $first = false;
    public bool $last = false;
}
