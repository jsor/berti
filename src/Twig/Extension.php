<?php

namespace Berti\Twig;

class Extension extends \Twig_Extension
{
    private $markdownRenderer;
    private $cwd;

    public function __construct(callable $markdownRenderer)
    {
        $this->markdownRenderer = $markdownRenderer;
    }

    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter(
                'markdown',
                [$this, 'renderMarkdown'],
                ['is_safe' => ['html']]
            )
        ];
    }

    public function setCwd(string $cwd = null)
    {
        $currentCwd = $this->cwd;
        $this->cwd = $cwd;

        return $currentCwd;
    }

    public function renderMarkdown(string $content)
    {
        return call_user_func($this->markdownRenderer, $content, $this->cwd);
    }

    public function getTokenParsers()
    {
        return [
            new MarkdownTokenParser()
        ];
    }
}
