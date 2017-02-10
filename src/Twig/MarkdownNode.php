<?php

namespace Berti\Twig;

class MarkdownNode extends \Twig_Node
{
    public function __construct(\Twig_Node $markdown, $lineno, $tag = 'markdown')
    {
        parent::__construct(['markdown' => $markdown], [], $lineno, $tag);
    }

    public function compile(\Twig_Compiler $compiler)
    {
        $compiler
            ->addDebugInfo($this)
            ->write('ob_start();' . PHP_EOL)
            ->subcompile($this->getNode('markdown'))
            ->write('$content = ob_get_clean();' . PHP_EOL)
            ->write('preg_match("/^\s*/", $content, $matches);' . PHP_EOL)
            ->write('$lines = explode("\n", $content);' . PHP_EOL)
            ->write('$content = preg_replace(\'/^\' . $matches[0]. \'/\', "", $lines);' . PHP_EOL)
            ->write('$content = implode("\n", $content);' . PHP_EOL)
            ->write('echo $this->env->getExtension(\Berti\Twig\Extension::class)->renderMarkdown($content);' . PHP_EOL);
    }
}
