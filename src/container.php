<?php

namespace Berti;

use Github;
use Http;
use Pimple\Container;
use React\Partial;

function container(array $values = []): Container
{
    $container = new Container;

    $container['input.directory_index'] = 'README.md';
    $container['output.directory_index'] = 'index.html';

    $container['twig.options'] = [];
    $container['twig.templates'] = function () use ($container) {
        $defaultTemplate = $container['template.default'];

        return [
            $defaultTemplate => '{{ berti.content|raw }}'
        ];
    };
    $container['twig.extension'] = function () use ($container) {
        return new Twig\Extension($container['markdown.renderer']);
    };
    $container['twig'] = function () use ($container) {
        $loader = new \Twig_Loader_Chain();

        $theme = $container['template.theme'];

        if ($theme) {
            $loader->addLoader(new \Twig_Loader_Filesystem($theme));
        }

        $loader->addLoader(new \Twig_Loader_Array($container['twig.templates']));

        $twig = new \Twig_Environment($loader, $container['twig.options']);

        $twig->addExtension($container['twig.extension']);

        return $twig;
    };

    $container['template.default'] = 'default.html.twig';
    $container['template.map'] = [];
    $container['template.theme'] = null;
    $container['template.renderer'] = function () use ($container) {
        return Partial\bind('Berti\twig_renderer', $container['twig']);
    };

    $container['github.repository_detector'] = function () {
        return Partial\bind(
            'Berti\github_repository_detector',
            'origin'
        );
    };
    $container['github.url_generator'] = function () {
        return 'Berti\github_url_generator';
    };
    $container['github.client'] = function () {
        $client = new Github\Client();

        if ($token = getenv('GITHUB_TOKEN')) {
            $client->authenticate(
                $token,
                null,
                Github\Client::AUTH_HTTP_TOKEN
            );
        }

        return $client;
    };
    $container['github.markdown.renderer'] = function () use ($container) {
        return Partial\bind(
            'Berti\github_markdown_renderer',
            $container['github.client'],
            $container['github.repository_detector'],
            $container['github.url_generator']
        );
    };

    $container['markdown.renderer'] = function () use ($container) {
        return $container['github.markdown.renderer'];
    };

    $container['document.finder'] = function () {
        return 'Berti\document_finder';
    };
    $container['document.collector'] = function () use ($container) {
        return Partial\bind(
            'Berti\document_collector',
            $container['document.finder'],
            Partial\placeholder(),
            Partial\placeholder(),
            $container['input.directory_index'],
            $container['output.directory_index']
        );
    };
    $container['document.template_selector'] = function () use ($container) {
        return Partial\bind(
            'Berti\document_template_selector',
            $container['template.default'],
            $container['template.map']
        );
    };
    $container['document.filter'] = function () {
        return 'Berti\document_output_rewrite_links_filter';
    };
    $container['document.processor'] = function () use ($container) {
        return Partial\bind(
            'Berti\document_processor',
            $container['markdown.renderer'],
            $container['template.renderer'],
            $container['document.template_selector'],
            $container['document.filter']
        );
    };

    $container['asset.finder'] = function () {
        return 'Berti\asset_finder';
    };
    $container['asset.collector'] = function () use ($container) {
        return Partial\bind(
            'Berti\asset_collector',
            $container['asset.finder']
        );
    };
    $container['asset.filter'] = function () {
        return function ($content, $asset, array $assetCollection) {
            return $content;
        };
    };
    $container['asset.processor'] = function () use ($container) {
        return Partial\bind(
            'Berti\asset_processor',
            $container['asset.filter']
        );
    };

    $container['mime_type.map'] = function () {
        return [
            'atom' => 'application/atom+xml',
            'json' => 'application/json',
            'rss' => 'application/rss+xml',
            'rdf' => 'application/xml',
            'xml' => 'application/xml',

            'js' => 'application/javascript',
            'css' => 'text/css',

            'webmanifest' => 'application/manifest+json',
            'webapp' => 'application/x-web-app-manifest+json',
            'appcache' => 'text/cache-manifest',

            'bmp' => 'image/bmp',
            'gif' => 'image/gif',
            'jpe' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'jpg' => 'image/jpeg',
            'png' => 'image/png',
            'svg' => 'image/svg+xml',
            'svgz' => 'image/svg+xml',

            'cur' => 'image/x-icon',
            'ico' => 'image/x-icon',

            'htm' => 'text/html',
            'html' => 'text/html',

            'eot' => 'application/vnd.ms-fontobject',
            'otf' => 'font/opentype',
            'ttf' => 'application/x-font-ttf',
            'woff' => 'application/font-woff',
            'woff2' => 'application/font-woff2',
        ];
    };
    $container['mime_type.detector'] = function () use ($container) {
        return function (\SplFileInfo $file, $content) use ($container) {
            $mimeType = 'text/plain';
            $ext = strtolower($file->getExtension());

            if (isset($container['mime_type.map'][$ext])) {
                $mimeType = $container['mime_type.map'][$ext];
            }

            if (
                0 === stripos($mimeType, 'text/') &&
                false === stripos($mimeType, 'charset=')
            ) {
                $mimeType .= '; charset=utf-8';
            }

            return $mimeType;
        };
    };

    $container['generator'] = function () use ($container) {
        return Partial\bind(
            'Berti\generator',
            $container['document.collector'],
            $container['document.processor'],
            $container['asset.collector'],
            $container['asset.processor']
        );
    };

    $container['server'] = function () {
        return 'Berti\server';
    };

    $container['console.commands'] = function () use ($container) {
        return [
            new Console\Command\GenerateCommand(
                $container['generator']
            ),
            new Console\Command\ServerCommand(
                $container['server']
            )
        ];
    };
    $container['console'] = function () use ($container) {
        return new Console\Application(
            $container['console.commands']
        );
    };

    foreach ($values as $key => $value) {
        $container[$key] = $value;
    }

    return $container;
}
