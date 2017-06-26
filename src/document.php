<?php

namespace Berti;

use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class Document
{
    public $input;
    public $output;

    public function __construct(SplFileInfo $input, SplFileInfo $output)
    {
        $this->input = $input;
        $this->output = $output;
    }
}

function document_finder($path): Finder
{
    return (new Finder())
        ->name('/\.(markdown|md|mdown)$/')
        ->files()
        ->notPath('/^vendor/')
        ->notPath('/^node_modules/')
        ->in($path);
}

function document_collector(
    callable $finder,
    string $path,
    string $targetDir,
    string $inputDirectoryIndex = 'README.md',
    string $outputDirectoryIndex = 'index.html'
): array
{
    $documents = [];

    foreach ($finder($path)->sortByName() as $file) {
        if ($inputDirectoryIndex === $file->getBasename()) {
            $filename = $outputDirectoryIndex;
        } else {
            $filename = $file->getBasename('.' . $file->getExtension()) . '.html';
        }

        $filename = strtolower($filename);

        $relativePath = ltrim(
            $file->getRelativePath() . DIRECTORY_SEPARATOR . $filename,
            DIRECTORY_SEPARATOR
        );

        $outputFile = new SplFileInfo(
            $targetDir . DIRECTORY_SEPARATOR . $relativePath,
            $file->getRelativePath(),
            ltrim($file->getRelativePath() . DIRECTORY_SEPARATOR . $filename, DIRECTORY_SEPARATOR)
        );

        $documents[] = new Document($file, $outputFile);
    }

    return $documents;
}

function document_processor(
    callable $markdownRenderer,
    callable $templateRenderer,
    callable $templateSelector,
    callable $outputFilter,
    string $buildDir,
    Document $document,
    array $documentCollection
): string
{
    $content = $markdownRenderer(
        $document->input->getContents(),
        [
            'cwd' => dirname($document->input->getRealPath())
        ]
    );

    $context = [
        'content' => $content,
        'title' => document_title_extractor($content),
        'document' => $document,
        'documents' => $documentCollection,
        'build_dir' => $buildDir,
        'relative_root' => uri_rewriter(
            '',
            '/',
            $document->output->getRelativePathname()
        )
    ];

    $template = $templateSelector($document);

    $rendered = $templateRenderer(
        $template,
        ['berti' => $context],
        dirname($document->input->getRealPath())
    );

    return $outputFilter($rendered, $document, $documentCollection);
}

function document_template_selector(
    string $defaultTemplate,
    array $templateMap,
    Document $document
): string
{
    $outputFile = $document->output->getRelativePathname();

    foreach ($templateMap as $file => $template) {
        $pattern = pattern_to_regex($file);

        if (!preg_match($pattern, $outputFile)) {
            continue;
        }

        return $template;
    }

    return $defaultTemplate;
}

function document_title_extractor(string $content): string
{
    $dom = new \DOMDocument();
    $success = $dom->loadHTML($content);

    if (!$success) {
        return '';
    }

    $xpath = new \DOMXPath($dom);

    foreach (range(1, 6) as $level) {
        $element = $xpath->evaluate('descendant-or-self::h' . $level)->item(0);

        if ($element && $element->textContent) {
            return $element->textContent;
        }
    }

    return '';
}

function document_output_rewrite_links_filter(
    string $content,
    Document $document,
    array $documentCollection,
    callable $fallbackUrlGenerator = null // @TODO Make first parameter in next major version
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

    $callback = function ($matches) use ($document, $fallbackUrlGenerator, $map) {
        $matchedUrl = trim($matches['url']);

        if (
            (isset($matchedUrl[0])  && '/' === $matchedUrl[0]) ||
            false !== strpos($matchedUrl, '://') ||
            0 === strpos($matchedUrl, 'data:')
        ) {
            return $matches[0];
        }

        $url = uri_rewriter(
            $matchedUrl,
            $document->output->getRelativePathname(),
            '/'
        );

        $hash = '';

        if (false !== strpos($url, '#')) {
            list($url, $hash) = explode('#', $url, 2);
        }

        $newUrl = $map[$url] ?? null;

        if (!$newUrl && $fallbackUrlGenerator) {
            $newUrl = $fallbackUrlGenerator(
                $matchedUrl,
                [
                    'cwd' => dirname($document->input->getRealPath())
                ]
            );
        }

        if (!$newUrl) {
            return $matches[0];
        }

        if ('' !== $hash) {
            $hash = '#' . $hash;
        }

        return str_replace($matchedUrl, $newUrl . $hash, $matches[0]);
    };

    $content = preg_replace_callback(
        '/href=(["\']?)(?P<url>.*?)\\1/i',
        $callback,
        $content
    );

    return $content;
}

function document_output_remove_github_anchor_prefix_filter(string $content): string
{
    $content = str_replace('href="#user-content-', 'href="#', $content);
    $content = str_replace('name="user-content-', 'name="', $content);
    $content = str_replace('id="user-content-', 'id="', $content);

    return $content;
}
