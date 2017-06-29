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

    return $client->api('markdown')->render(
        $content,
        'markdown',
        $repository
    );
}

function github_repository_detector(string $remote, string $cwd = null): string
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

        list(, $second) = explode("\t", $line, 2);
        list($url) = explode(' ', $second, 2);
        $url = str_replace('git@', '', $url);
        $parts = parse_url($url);

        $path = trim($parts['path'], '/');

        if ('.git' === substr($path, -4)) {
            $path = substr($path, 0, -4);
        }

        return $path;
    }

    return '';
}
