<?php

namespace Berti;

use Berti\Twig\Extension;

function twig_renderer(
    \Twig_Environment $twig,
    string $name,
    array $context = [],
    string $cwd = null
): string
{
    $currentCwd = null;

    if ($twig->hasExtension(Extension::class)) {
        $currentCwd = $twig->getExtension(Extension::class)->setCwd($cwd);
    }

    $content = $twig->render($name, $context);

    if ($twig->hasExtension(Extension::class)) {
        $twig->getExtension(Extension::class)->setCwd($currentCwd);
    }

    return $content;
}
