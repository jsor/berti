<?php

namespace Berti;

use Github;
use Symfony\Component\Process\Process;

function github_markdown_renderer(
    Github\Client $client,
    callable $repositoryDetector,
    callable $markdownFilter,
    string $content,
    Document $document,
    array $documentCollection,
    array $assetCollection
): string
{
    $repository = $repositoryDetector(dirname($document->input->getRealPath())) ?: null;

    $html = $client->api('markdown')->render(
        $content,
        'markdown',
        $repository
    );

    return $markdownFilter(
        $repository,
        $html,
        $document,
        $documentCollection,
        $assetCollection
    );
}

function github_anchor_rewriter(
    string $html
): string
{
    return strtr(
        $html,
        [
            'href="#user-content-' => 'href="#',
            'name="user-content-' => 'name="',
            'id="user-content-' => 'id="'
        ]
    );
}

function github_relative_to_absolute_link_rewriter(
    callable $urlGenerator,
    string $repository,
    string $html,
    Document $document,
    array $documentCollection,
    array $assetCollection
): string
{
    $map = [];

    foreach ($assetCollection as $asset) {
        $map[$asset->input->getRelativePathname()] = uri_rewriter(
            $asset->output->getRelativePathname(),
            '/',
            $document->output->getRelativePathname()
        );

        if ('index.html' === $asset->output->getBasename()) {
            $dirName = dirname($asset->input->getRelativePathname());
            $uri = uri_rewriter(
                dirname($asset->output->getRelativePathname()),
                '/',
                $document->output->getRelativePathname()
            );

            $map[$dirName] = $uri;
            $map[$dirName . '/'] = $uri;
        }
    }

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
                    $url,
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
