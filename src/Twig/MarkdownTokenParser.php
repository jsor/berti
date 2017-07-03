<?php

namespace Berti\Twig;

class MarkdownTokenParser extends \Twig_TokenParser
{
    public function parse(\Twig_Token $token): MarkdownNode
    {
        $lineno = $token->getLine();
        $stream = $this->parser->getStream();

        $stream->expect(\Twig_Token::BLOCK_END_TYPE);

        $body = $this->parser->subparse(
            function(\Twig_Token $token) {
                return $token->test('end' . $this->getTag());
            },
            true
        );

        $stream->expect(\Twig_Token::BLOCK_END_TYPE);

        return new MarkdownNode(
            $body,
            (int) $lineno,
            $this->getTag()
        );
    }

    public function getTag(): string
    {
        return 'markdown';
    }
}
