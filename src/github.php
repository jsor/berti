<?php

namespace Berti;

use Github;
use Symfony\Component\Process\Process;

function github_markdown_renderer(
    Github\Client $client,
    callable $repositoryDetector,
    string $content,
    array $options = []
): string
{
    $repository = null;

    if (array_key_exists('repo', $options)) {
        $repository = $options['repo'];
    }

    if (array_key_exists('repository', $options)) {
        $repository = $options['repository'];
    }

    if (!$repository) {
        $repository = $repositoryDetector($options['cwd'] ?? null) ?: null;
    }

    $html = $client->api('markdown')->render(
        $content,
        'markdown',
        $repository
    );

    $html = strtr(
        $html,
        [
            'href="#user-content-' => 'href="#',
            'name="user-content-' => 'name="',
            'id="user-content-' => 'id="'
        ]
    );

    return $html;
}

function github_repository_detector(string $remote, string $cwd = null): ?string
{
    $process = new Process('git remote -v', $cwd ?: null);
    $process->run();

    $output = $process->getOutput();

    foreach (explode("\n", $output) as $line) {
        if (0 !== strpos($line, $remote)) {
            continue;
        }

        if (false === stripos($line, 'github.com')) {
            continue;
        }

        $second = explode("\t", $line, 2)[1];
        $url = explode(' ', $second, 2)[0];
        $url = str_replace('git@', '', $url);
        $parts = parse_url($url);

        $path = trim($parts['path'], '/');

        if ('.git' === substr($path, -4)) {
            $path = substr($path, 0, -4);
        }

        return $path;
    }

    return null;
}
