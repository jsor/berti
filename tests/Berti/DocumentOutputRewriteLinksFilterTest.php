<?php

namespace Berti;

use Symfony\Component\Finder\SplFileInfo;

class DocumentOutputRewriteLinksFilterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider provideUrls
     */
    public function testUrls($format, $document, $documentCollection, $inputUrl, $expectedUrl)
    {
        $content = sprintf($format, $inputUrl);

        $filtered = document_output_rewrite_links_filter($content, $document, $documentCollection);

        $this->assertEquals(sprintf($format, $expectedUrl), $filtered, 'document_output_rewrite_links_filter() rewrites relative urls');
    }

    public function provideUrls()
    {
        $document = new Document(
            new SplFileInfo(
                '/path/to/docs/cookbook/README.md',
                'docs/cookbook/',
                'docs/cookbook/README.md'
            ),
            new SplFileInfo(
                '/path/to/docs/cookbook/index.html',
                'docs/cookbook/',
                'docs/cookbook/index.html'
            )
        );

        $documentCollection = [
            'docs/README.md' => new Document(
                new SplFileInfo(
                    '/path/to/docs/README.md',
                    'docs/',
                    'docs/README.md'
                ),
                new SplFileInfo(
                    '/path/to/docs/index.html',
                    'docs/',
                    'docs/index.html'
                )
            ),
            'docs/cookbook/README.md' => new Document(
                new SplFileInfo(
                    '/path/to/docs/cookbook/README.md',
                    'docs/cookbook',
                    'docs/cookbook/README.md'
                ),
                new SplFileInfo(
                    '/path/to/docs/cookbook/index.html',
                    'docs/cookbook',
                    'docs/cookbook/index.html'
                )
            ),
            'docs/reference/Article.md' => new Document(
                new SplFileInfo(
                    '/path/to/docs/reference/Article.md',
                    'docs/reference',
                    'docs/reference/Article.md'
                ),
                new SplFileInfo(
                    '/path/to/docs/reference/article.html',
                    'docs/reference',
                    'docs/reference/article.html'
                )
            )
        ];

        return [
            [
                '<a href="%s">Link</a>',
                $document,
                $documentCollection,
                '../reference/Article.md',
                '../reference/article.html'
            ],
            [
                '<a href=\'%s\'>Link</a>',
                $document,
                $documentCollection,
                '../reference/Article.md',
                '../reference/article.html'
            ],
        ];
    }

    /**
     * @dataProvider provideMultipleUrls
     */
   public function testMultipleUrls($format, $sourceFile, $destFile, $inputUrl1, $inputUrl2, $expectedUrl1, $expectedUrl2)
    {
        $content = sprintf($format, $inputUrl1, $inputUrl2);

        $filtered = document_output_rewrite_links_filter($content, $sourceFile, $destFile, null);

        $this->assertEquals(sprintf($format, $expectedUrl1, $expectedUrl2), $filtered, 'document_output_rewrite_links_filter() rewrites relative urls');
    }

    public function provideMultipleUrls()
    {
        $document = new Document(
            new SplFileInfo(
                '/path/to/docs/cookbook/README.md',
                'docs/cookbook/',
                'docs/cookbook/README.md'
            ),
            new SplFileInfo(
                '/path/to/docs/cookbook/index.html',
                'docs/cookbook/',
                'docs/cookbook/index.html'
            )
        );

        $documentCollection = [
            'docs/README.md' => new Document(
                new SplFileInfo(
                    '/path/to/docs/README.md',
                    'docs/',
                    'docs/README.md'
                ),
                new SplFileInfo(
                    '/path/to/docs/index.html',
                    'docs/',
                    'docs/index.html'
                )
            ),
            'docs/cookbook/README.md' => new Document(
                new SplFileInfo(
                    '/path/to/docs/cookbook/README.md',
                    'docs/cookbook',
                    'docs/cookbook/README.md'
                ),
                new SplFileInfo(
                    '/path/to/docs/cookbook/index.html',
                    'docs/cookbook',
                    'docs/cookbook/index.html'
                )
            ),
            'docs/reference/Article.md' => new Document(
                new SplFileInfo(
                    '/path/to/docs/reference/Article.md',
                    'docs/reference',
                    'docs/reference/Article.md'
                ),
                new SplFileInfo(
                    '/path/to/docs/reference/article.html',
                    'docs/reference',
                    'docs/reference/article.html'
                )
            )
        ];

        return [
            [
                '<a href="%s">Link</a>',
                $document,
                $documentCollection,
                '../reference/Article.md',
                'README.md',
                '../reference/article.html',
                'index.html'
            ],
            [
                '<a href=\'%s\'>Link</a>',
                $document,
                $documentCollection,
                '../reference/Article.md',
                'README.md',
                '../reference/article.html',
                'index.html'
            ],
        ];
    }
}
