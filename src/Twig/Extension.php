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
                ['is_safe' => ['html']]
            )
        ];
    }

    public function setCwd(string $cwd = null): ?string
    {
        $currentCwd = $this->cwd;
        $this->cwd = $cwd;

        return $currentCwd;
    }

    public function markdown(string $content, array $options = []): string
    {
        if (!array_key_exists('cwd', $options)) {
            $options['cwd'] = $this->cwd;
        }

        return ($this->markdownRenderer)($content, $options);
    }
}
