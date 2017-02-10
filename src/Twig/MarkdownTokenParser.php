<?php

namespace Berti\Twig;

class MarkdownTokenParser extends \Twig_TokenParser
{
    public function parse(\Twig_Token $token)
    {
        $lineno = $token->getLine();

        $this->parser->getStream()->expect(\Twig_Token::BLOCK_END_TYPE);
        $markdown = $this->parser->subparse(array($this, 'decideMarkdownEnd'), true);
        $this->parser->getStream()->expect(\Twig_Token::BLOCK_END_TYPE);

        return new MarkdownNode($markdown, $lineno, $this->getTag());
    }

    public function decideMarkdownEnd(\Twig_Token $token)
    {
        return $token->test('endmarkdown');
    }

    public function getTag()
    {
        return 'markdown';
    }
}
