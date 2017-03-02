<?php

namespace Berti;

use Lurker\Event\FilesystemEvent;
use Lurker\ResourceWatcher;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

function watcher(
    callable $generator,
    callable $documentFinder, 
    string $themePath = null,
    string $buildDir,
    OutputInterface $output
)
{
    $filesystem = new Filesystem();

    if (!$filesystem->isAbsolutePath($buildDir)) {
        $buildDir = uri_canonicalizer(getcwd().DIRECTORY_SEPARATOR.$buildDir, DIRECTORY_SEPARATOR);
    }

    $output->writeln('Watching...');

    $translateTypeString = function(string $typeString) {
        switch ($typeString) {
            case 'modify':
                return 'modified';
            default:
                return $typeString . 'd';
        }
    };

    $watcher = new ResourceWatcher();

    $build = function ($file, $type) use ($generator, $buildDir, $output, $translateTypeString) {
        $output->writeln('');
        $output->writeln(sprintf(
            '%s <info>%s</info>',
            $file,
            $translateTypeString($type)
        ));
        $output->writeln('');

        $generator($buildDir, $output);
    };

    $track = function ($id, $path) use ($watcher, $build) {
        $watcher->track($id, $path, FilesystemEvent::MODIFY | FilesystemEvent::DELETE);
        $watcher->addListener($id, function (FilesystemEvent $event) use ($build) {
            $build($event->getResource(), $event->getTypeString());
        });
    };

    $documents = $documentFinder(getcwd());

    foreach ($documents as $document) {
        $track($document->getRelativePathname(), $document->getPathname());
    }

    $watcher->track('new', getcwd(), FilesystemEvent::CREATE);
    $watcher->addListener('new', function (FilesystemEvent $event) use ($documentFinder, $track, $build) {
        $documents = $documentFinder(getcwd());

        foreach ($documents as $document) {
            if ($document->getPathname() !== (string) $event->getResource()) {
                continue;
            }

            $track($document->getRelativePathname(), $document->getPathname());
            $build($event->getResource(), $event->getTypeString());
            break;
        }
    });

    if ($themePath) {
        $track('theme.path', $themePath);
    }

    $watcher->start();

    $output->writeln('Stopped');
}
