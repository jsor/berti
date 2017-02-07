<?php

namespace Berti;

use Github;

function github_markdown_renderer(
    Github\Client $client,
    string $repository,
    string $text
): string
{
    return $client->api('markdown')->render($text, 'markdown', $repository);
}

function github_repository_detector(): string
{
    exec('git remote -v', $output);

    foreach (explode("\n", $output[0]) as $line) {
        if (false === strpos($line, 'github.com')) {
            continue;
        }

        list(, $second) = explode("\t", $line, 2);
        list($url) = explode(' ', $second, 2);
        $url = str_replace('git@', '', $url);
        $parts = parse_url($url);

        return substr($parts['path'], 0, -4);
    }

    return null;
}
