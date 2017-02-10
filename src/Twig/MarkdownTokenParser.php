<?php

namespace Berti\Twig;

class MarkdownTokenParser extends \Twig_TokenParser
{
    public function parse(\Twig_Token $token)
    {
        $lineno = $token->getLine();
        $stream = $this->parser->getStream();

        $options = [];

        while (!$stream->test(\Twig_Token::BLOCK_END_TYPE)) {
            if (!$stream->test(\Twig_Token::NAME_TYPE)) {
                $token = $stream->getCurrent();

                throw new \Twig_Error_Syntax(
                    sprintf(
                        'Unexpected token "%s" of value "%s"',
                        \Twig_Token::typeToEnglish($token->getType()),
                        $token->getValue()
                    ),
                    $token->getLine()
                );
            }

            $name = $stream->getCurrent()->getValue();
            $stream->next();
            $stream->expect(\Twig_Token::OPERATOR_TYPE, '=');
            $options[$name] = $this->parser->getExpressionParser()->parseExpression();
        }

        $stream->expect(\Twig_Token::BLOCK_END_TYPE);

        $body = $this->parser->subparse(
            function(\Twig_Token $token) {
                return $token->test('end' . $this->getTag());
            },
            true
        );

        $stream->expect(\Twig_Token::BLOCK_END_TYPE);

        return new MarkdownNode($body, new \Twig_Node($options), $lineno, $this->getTag());
    }

    public function getTag()
    {
        return 'markdown';
    }
}
