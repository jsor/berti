<?php

namespace Berti;

use Github;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Process\Process;

function github_markdown_renderer(
    Github\Client $client,
    callable $repositoryDetector,
    SplFileInfo $document
): string
{
    return $client->api('markdown')->render(
        $document->getContents(),
        'markdown',
        $repositoryDetector($document) ?: null
    );
}

function github_repository_detector(SplFileInfo $document): string
{
    $cwd = $document->getRealPath();

    if (!$document->isDir()) {
        $cwd = dirname($cwd);
    }

    $process = new Process('git remote -v', $cwd);
    $process->run();

    $output = $process->getOutput();

    foreach (explode("\n", $output) as $line) {
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
