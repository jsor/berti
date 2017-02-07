<?php

namespace Berti;

function twig_renderer(
    \Twig_Environment $twig,
    string $name,
    array $context = []
): string
{
    return $twig->render($name, $context);
}
