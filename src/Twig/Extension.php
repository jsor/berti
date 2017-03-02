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

    public function getTokenParsers()
    {
        return [
            new MarkdownTokenParser()
        ];
    }

    public function getFilters()
    {
        return [
            new \Twig_Filter(
                'markdown',
                [$this, 'markdown'],
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

    public function markdown(string $content, array $options = [])
    {
        if (!array_key_exists('cwd', $options)) {
            $options['cwd'] = $this->cwd;
        }

        return ($this->markdownRenderer)($content, $options);
    }
}
