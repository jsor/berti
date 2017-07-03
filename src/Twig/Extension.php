<?php

namespace Berti\Twig;

class Extension extends \Twig_Extension
{
    private $markdownRenderer;

    public function __construct(callable $markdownRenderer)
    {
        $this->markdownRenderer = $markdownRenderer;
    }

    public function getTokenParsers(): array
    {
        return [
            new MarkdownTokenParser()
        ];
    }

    public function getFilters(): array
    {
        return [
            new \Twig_Filter(
                'markdown',
                [$this, 'markdown'],
                [
                    'is_safe' => ['html'],
                    'needs_context' => true
                ]
            )
        ];
    }

    public function markdown(array $twigContext, string $content): string
    {
        return ($this->markdownRenderer)(
            $twigContext['berti']['document'],
            $twigContext['berti']['documents'],
            $content
        );
    }
}
