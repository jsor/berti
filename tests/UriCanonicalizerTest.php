<?php

namespace Berti;

use PHPUnit\Framework\TestCase;

class UriCanonicalizerTest extends TestCase
{
    /**
     * @dataProvider provideUrls
     */
    public function testUrls(string $inputUrl, string $expectedUrl, string $separator)
    {
        $canonicalized = uri_canonicalizer($inputUrl, $separator);

        self::assertEquals($expectedUrl, $canonicalized);
    }

    public function provideUrls(): array
    {
        return [
            ['reference/../reference/article.html', 'reference/article.html', '/'],
            ['/././article.html', '/article.html', '/'],
            ['././article.html', 'article.html', '/'],
            ['/../htdocs/article.html', '/article.html', '/'],
            ['///.//./article.html', '/article.html', '/'],

            // No transformation
            ['../../reference/article.html', '../../reference/article.html', '/'],
            ['reference/article.html', 'reference/article.html', '/'],
            ['/article.html', '/article.html', '/'],

            // Normalization
            ['reference\../reference\article.html', 'reference/article.html', '/'],
            ['reference\../reference\article.html', 'reference\article.html', '\\'],

            // Invalid urls
            ['/../article.html', '/', '/'],
            ['///../article.html', '/', '/'],
            ['/folder/../../article.html', '/', '/'],

            // Absolute urls
            ['http://www.example.com/', 'http://www.example.com/', '/'],
            ['http://www.example.com', 'http://www.example.com', '/'],
            ['http://www.example.com/reference/../reference/article.html', 'http://www.example.com/reference/article.html', '/'],

            // Protocol relative urls
            ['//www.example.com/', '//www.example.com/', '/'],
            ['//www.example.com', '//www.example.com', '/'],
            ['//www.example.com/reference/../reference/article.html', '//www.example.com/reference/article.html', '/'],
        ];
    }
}
