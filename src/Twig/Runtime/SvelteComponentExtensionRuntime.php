<?php

namespace App\Twig\Runtime;

use Twig\Extension\RuntimeExtensionInterface;
use Symfony\WebpackEncoreBundle\Twig\StimulusTwigExtension;
use Twig\Environment;

class SvelteComponentExtensionRuntime implements RuntimeExtensionInterface
{
    private $stimulusExtension;

    public function __construct(StimulusTwigExtension $stimulusExtension)
    {
        $this->stimulusExtension = $stimulusExtension;
    }

    public function renderSvelteComponent(Environment $env, string $componentName, array $props = [], bool $intro = false): string
    {
        $params = ['component' => $componentName];
        if ($props) {
            $params['props'] = $props;
        }
        if ($intro) {
            $params['intro'] = true;
        }

        return $this->stimulusExtension->renderStimulusController($env, '@symfony/ux-svelte/svelte', $params);
    }
}
