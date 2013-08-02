<?php

namespace Berti;

use Ciconia\Ciconia;
use Ciconia\Extension\Gfm;
use Github;
use React\Curry;

function container(array $values = [])
{
    $container = new \Pimple;

    $container['input.directory_index'] = 'README.md';
    $container['output.directory_index'] = 'index.html';

    $container['twig.options'] = [];
    $container['twig.templates'] = function() use ($container) {
        $defaultTemplate = $container['template.default_filename'];
        $homepageTemplate = $container['template.homepage_filename'];

        return [
            $defaultTemplate => '{{ berti.content|raw }}',
            $homepageTemplate => '{{ berti.content|raw }}'
        ];
    };
    $container['twig'] = $container->share(function() use ($container) {
        $loader = new \Twig_Loader_Chain();

        $theme = $container['template.theme'];

        if ($theme) {
            $loader->addLoader(new \Twig_Loader_Filesystem($theme));
        }

        $loader->addLoader(new \Twig_Loader_Array($container['twig.templates']));

        return new \Twig_Environment($loader, $container['twig.options']);
    });

    $container['template.default_filename'] = 'default.html.twig';
    $container['template.homepage_filename'] = 'default.html.twig';
    $container['template.theme'] = null;
    $container['template.renderer'] = $container->share(function () use ($container) {
        return Curry\bind('Berti\twig_renderer', $container['twig']);
    });

    $container['github.repository'] = $container->share(function () {
        return github_repository_detector();
    });
    $container['github.client'] = $container->share(function () {
        return new Github\Client();
    });
    $container['github.client'] = $container->share(function () {
        return new Github\Client();
    });
    $container['github.markdown.renderer'] = $container->share(function () use ($container) {
        return Curry\bind(
            'Berti\github_markdown_renderer',
            $container['github.client'],
            $container['github.repository']
        );
    });

    $container['ciconia'] = $container->share(function () {
        $ciconia = new Ciconia();
        $ciconia->addExtension(new Gfm\FencedCodeBlockExtension());
        $ciconia->addExtension(new Gfm\TaskListExtension());
        $ciconia->addExtension(new Gfm\InlineStyleExtension());
        $ciconia->addExtension(new Gfm\WhiteSpaceExtension());

        return $ciconia;
    });
    $container['ciconia.markdown.renderer'] = $container->share(function () use ($container) {
        return array($container['ciconia'], 'render');
    });

    $container['markdown.renderer'] = function () use ($container) {
        return $container['github.markdown.renderer'];
    };

    $container['document.finder'] = function () {
        return 'Berti\document_finder';
    };
    $container['document.collector'] = $container->share(function() use ($container) {
        return Curry\bind(
            'Berti\document_collector',
            $container['document.finder'],
            Curry\placeholder(),
            Curry\placeholder(),
            $container['input.directory_index'],
            $container['output.directory_index']
        );
    });
    $container['document.template_selector'] = $container->share(function() use ($container) {
        return Curry\bind(
            'Berti\document_template_selector',
            $container['output.directory_index'],
            $container['template.default_filename'],
            $container['template.homepage_filename']
        );
    });
    $container['document.output_content_filter'] = $container->protect(function($content, $document, array $documentCollection) {
        $content = document_output_rewrite_links_filter($content, $document, $documentCollection);

        return $content;
    });
    $container['document.processor'] = $container->share(function() use ($container) {
        return Curry\bind(
            'Berti\document_processor',
            $container['markdown.renderer'],
            $container['template.renderer'],
            $container['document.template_selector'],
            $container['document.output_content_filter']
        );
    });

    $container['console.commands'] = $container->share(function() use ($container) {
        return [
            new Console\Command\GenerateCommand(
                $container['document.collector'],
                $container['document.processor']
            ),
            new Console\Command\WatchCommand(
                $container['document.finder'],
                $container['template.theme']
            )
        ];
    });
    $container['console'] = $container->share(function() use ($container) {
        return new Console\Application(
            $container['console.commands']
        );
    });

    foreach ($values as $key => $value) {
        $container[$key] = $value;
    }

    return $container;
}
