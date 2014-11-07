<?php

namespace Berti;

use Ciconia\Ciconia;
use Ciconia\Extension\Gfm;
use Github;
use Pimple\Container;
use React\Partial;

function container(array $values = [])
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
    $container['twig'] = function () use ($container) {
        $loader = new \Twig_Loader_Chain();

        $theme = $container['template.theme'];

        if ($theme) {
            $loader->addLoader(new \Twig_Loader_Filesystem($theme));
        }

        $loader->addLoader(new \Twig_Loader_Array($container['twig.templates']));

        return new \Twig_Environment($loader, $container['twig.options']);
    };

    $container['template.default'] = 'default.html.twig';
    $container['template.map'] = [];
    $container['template.theme'] = null;
    $container['template.renderer'] = function () use ($container) {
        return Partial\bind('Berti\twig_renderer', $container['twig']);
    };

    $container['github.repository'] = function () {
        return github_repository_detector();
    };
    $container['github.client'] = function () {
        return new Github\Client();
    };
    $container['github.markdown.renderer'] = function () use ($container) {
        return Partial\bind(
            'Berti\github_markdown_renderer',
            $container['github.client'],
            $container['github.repository']
        );
    };

    $container['ciconia'] = function () {
        $ciconia = new Ciconia();
        $ciconia->addExtension(new Gfm\FencedCodeBlockExtension());
        $ciconia->addExtension(new Gfm\TaskListExtension());
        $ciconia->addExtension(new Gfm\InlineStyleExtension());
        $ciconia->addExtension(new Gfm\WhiteSpaceExtension());

        return $ciconia;
    };
    $container['ciconia.markdown.renderer'] = function () use ($container) {
        return array($container['ciconia'], 'render');
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
    $container['document.filter'] = $container->protect(function ($content, $document, array $documentCollection) {
        $content = document_output_rewrite_links_filter($content, $document, $documentCollection);
        $content = document_output_remove_github_anchor_prefix_filter($content);

        return $content;
    });
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
    $container['asset.filter'] = $container->protect(function ($content, $asset, array $assetCollection) {
        return $content;
    });
    $container['asset.processor'] = function () use ($container) {
        return Partial\bind(
            'Berti\asset_processor',
            $container['asset.filter']
        );
    };

    $container['console.commands'] = function () use ($container) {
        return [
            new Console\Command\GenerateCommand(
                $container['document.collector'],
                $container['document.processor'],
                $container['asset.collector'],
                $container['asset.processor']
            ),
            new Console\Command\WatchCommand(
                $container['document.finder'],
                $container['template.theme']
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
