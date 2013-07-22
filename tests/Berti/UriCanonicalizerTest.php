<?php

namespace Berti;

class UriCanonicalizerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider provideUrls
     */
    public function testUrls($inputUrl, $expectedUrl, $separator)
    {
        $canonicalized = uri_canonicalizer($inputUrl, $separator);

        $this->assertEquals($expectedUrl, $canonicalized);
    }

    public function provideUrls()
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
            ['/folder/../../article.html', '/', '/'],
        ];
    }
}
