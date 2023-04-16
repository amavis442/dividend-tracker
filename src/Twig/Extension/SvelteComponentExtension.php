<?php

namespace App\Twig\Extension;


use App\Twig\Runtime\SvelteComponentExtensionRuntime;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class SvelteComponentExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('svelte_component', [$this, 'renderSvelteComponent'], ['needs_environment' => true, 'is_safe' => ['html_attr']]),
        ];
    }
}
