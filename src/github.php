<?php

namespace Berti;

use Github;
use Symfony\Component\Process\Process;

function github_markdown_renderer(
    Github\Client $client,
    callable $repositoryDetector,
    callable $urlGenerator,
    Document $document,
    array $documentCollection,
    string $content
): string
{
    $repository = $repositoryDetector(dirname($document->input->getRealPath())) ?: null;

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

    $html = github_relative_to_absolute_link_converter(
        $urlGenerator,
        $document,
        $documentCollection,
        $repository,
        $html
    );

    return $html;
}

function github_relative_to_absolute_link_converter(
    callable $urlGenerator,
    Document $document,
    array $documentCollection,
    string $repository,
    string $html
): string
{
    $map = [];

    foreach ($documentCollection as $doc) {
        $map[$doc->input->getRelativePathname()] = uri_rewriter(
            $doc->output->getRelativePathname(),
            '/',
            $document->output->getRelativePathname()
        );
    }

    $callback = function ($matches) use ($urlGenerator, $repository, $document, $map) {
        $matchedUrl = trim($matches['url']);

        if (
            (isset($matchedUrl[0])  && '/' === $matchedUrl[0]) ||
            false !== strpos($matchedUrl, '://') ||
            0 === strpos($matchedUrl, '#') ||
            0 === strpos($matchedUrl, 'data:')
        ) {
            return $matches[0];
        }

        $url = uri_rewriter(
            $matchedUrl,
            $document->output->getRelativePathname(),
            '/'
        );

        if (false !== strpos($url, '#')) {
            [$url] = explode('#', $url, 2);
        }

        if (isset($map[$url])) {
            return $matches[0];
        }

        return str_replace(
            $matchedUrl,
            uri_canonicalizer(
                $urlGenerator(
                    $repository,
                    $matchedUrl,
                    dirname($document->input->getRealPath())
                )
            ),
            $matches[0]
        );
    };

    $content = preg_replace_callback(
        '/href=(["\']?)(?P<url>.*?)\\1/i',
        $callback,
        $html
    );

    return $content;
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

function github_url_generator(
    string $repository,
    string $url,
    string $cwd = null
): string
{
    $process = new Process('git rev-parse HEAD', $cwd ?: null);
    $process->run();

    $commit = $process->getOutput();

    // Always use /blob as github redirects to /tree for directories
    return 'https://github.com/' . $repository . '/blob/' . $commit . '/' . ltrim($url, '/');
}
