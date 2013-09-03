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

function document_finder($path)
{
    $finder = new Finder();

    return $finder
        ->name('/\.(markdown|md|mdown)$/')
        ->files()
        ->in($path)
    ;
}

function document_collector($finder, $path, $targetDir, $inputDirectoryIndex = 'README.md', $outputDirectoryIndex = 'index.html')
{
    $documents = [];

    foreach ($finder($path)->sortByName() as $file) {
        if ($inputDirectoryIndex === $file->getBasename()) {
            $filename = $outputDirectoryIndex;
        } else {
            $filename = $file->getBasename('.'.$file->getExtension()) . '.html';
        }

        $filename = strtolower($filename);

        $relativePath = ltrim($file->getRelativePath().DIRECTORY_SEPARATOR.$filename, DIRECTORY_SEPARATOR);

        $outputFile = new SplFileInfo(
            $targetDir.DIRECTORY_SEPARATOR.$relativePath,
            $file->getRelativePath(),
            ltrim($file->getRelativePath().DIRECTORY_SEPARATOR.$filename, DIRECTORY_SEPARATOR)
        );

        $documents[] = new Document($file, $outputFile);
    }

    return $documents;
}

function document_processor($markdownRenderer, $templateRenderer, $templateSelector, $outputFilter, Document $document, array $documentCollection)
{
    $context = [
        'content' => $markdownRenderer($document->input->getContents()),
        'document' => $document,
    ];

    $template = $templateSelector($document);

    $rendered = $templateRenderer($template, ['berti' => $context]);

    return $outputFilter($rendered, $document, $documentCollection);
}

function document_template_selector($defaultTemplate, array $templateMap, Document $document)
{
    $outputFile = $document->output->getRelativePathname();

    if (array_key_exists($outputFile, $templateMap)) {
        return $templateMap[$outputFile];
    } else {
        return $defaultTemplate;
    }
}

function document_output_rewrite_links_filter($content, Document $document, array $documentCollection)
{
    $map = [];

    foreach ($documentCollection as $doc) {
        $map[$doc->input->getRelativePathname()] = uri_rewriter(
            $doc->output->getRelativePathname(),
            '/',
            $document->output->getRelativePathname()
        );
    }

    $callback = function ($matches) use ($document, $map) {
        $matchedUrl = trim($matches['url']);

        $url = uri_rewriter(
            $matchedUrl,
            $document->output->getRelativePathname(),
            '/'
        );

        if (!isset($map[$url])) {
            return $matches[0];
        }

        return str_replace($matchedUrl, $map[$url], $matches[0]);
    };

    $content = preg_replace_callback('/href=(["\']?)(?P<url>.*?)\\1/i', $callback, $content);

    return $content;
}
