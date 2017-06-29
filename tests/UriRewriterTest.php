<?php

namespace Berti;

use PHPUnit\Framework\TestCase;

class UriRewriterTest extends TestCase
{
    /**
     * @dataProvider provideUrls
     */
    public function testUrls(string $sourceFile, string $targetFile, string $inputUrl, string $expectedUrl)
    {
        $filtered = uri_rewriter($inputUrl, $sourceFile, $targetFile);

        self::assertEquals($expectedUrl, $filtered, 'rewrite_url() rewrites relative urls');
    }

    public function provideUrls(): array
    {
        return [
            // path diffs
            ['docs/reference/index.html', 'docs/cookbook/index.html', '../../reference/article.html', '../../reference/article.html'],
            ['docs/index.html', 'about.html', '../reference/article.html', 'reference/article.html'],
            ['index.html', 'docs/about.html', 'reference/article.html', '../reference/article.html'],
            ['index.html', 'docs/cookbook/index.html', 'docs/cookbook/article.html', 'article.html'],
            ['docs/reference/index.html', 'docs/about.html', '../../reference/article.html', '../reference/article.html'],
            ['docs/reference/index.html', 'examples/basics/index.html', '../reference/article.html', '../../docs/reference/article.html'],
            ['docs/index.html', 'docs/about.html', 'article.html', 'article.html'],
            ['docs/index.html', 'docs/about.html', '../reference/article.html', '../reference/article.html'],
            ['docs/index.html', 'about.html', '.././reference/article.html', 'reference/article.html'],

            // url diffs
            ['docs/index.html', 'docs/cookbook/index.html', 'http://foo.com/article.html', 'http://foo.com/article.html'],
            ['docs/index.html', 'docs/cookbook/index.html', '//foo.com/reference/article.html', '//foo.com/reference/article.html'],

            // url with data:
            ['docs/index.html', 'docs/cookbook/index.html', 'data:image/png;base64,abcdef=', 'data:image/png;base64,abcdef='],
            ['docs/index.html', 'docs/cookbook/index.html', '../docs/bg-data:.gif', '../../docs/bg-data:.gif'],
        ];
    }
}
