<?php

namespace Berti;

function twig_renderer(\Twig_Environment $twig, $name, array $context = [])
{
    return $twig->render($name, $context);
}
