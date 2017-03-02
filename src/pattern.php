<?php

namespace Berti;

use Symfony\Component\Finder\Glob;

function pattern_to_regex(string $str): string
{
    return pattern_is_regex($str) ? $str : Glob::toRegex($str);
}

/**
 * Taken from Symfony\Component\Finder\Iterator\MultiplePcreFilterIterator
 */
function pattern_is_regex(string $str): bool
{
    if (preg_match('/^(.{3,}?)[imsxuADU]*$/', $str, $m)) {
        $start = substr($m[1], 0, 1);
        $end = substr($m[1], -1);

        if ($start === $end) {
            return !preg_match('/[*?[:alnum:] \\\\]/', $start);
        }

        foreach (array(array('{', '}'), array('(', ')'), array('[', ']'), array('<', '>')) as $delimiters) {
            if ($start === $delimiters[0] && $end === $delimiters[1]) {
                return true;
            }
        }
    }

    return false;
}
