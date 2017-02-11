<?php

namespace Berti;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

function generator(
    callable $documentCollector,
    callable $documentProcessor,
    callable $assetCollector,
    callable $assetProcessor,
    string $buildDir,
    OutputInterface $output
)
{
    $filesystem = new Filesystem();

    // ---

    if (!$filesystem->isAbsolutePath($buildDir)) {
        $buildDir = uri_canonicalizer(getcwd().DIRECTORY_SEPARATOR.$buildDir, DIRECTORY_SEPARATOR);
    }

    $output->writeln(sprintf('<info>Writing build to: %s</info>', $buildDir));

    // ---

    $output->writeln('Starting build');

    $output->writeln('Processing assets...');

    $assets = call_user_func(
        $assetCollector,
        getcwd(),
        $buildDir
    );

    foreach ($assets as $asset) {
        $output->write(sprintf(
            '<comment>==> Processing %s -> %s</comment>...',
            $asset->input->getRelativePathname(),
            $asset->output->getRelativePathname()
        ));

        $content = call_user_func($assetProcessor, $asset, $assets);

        $filesystem->dumpFile($asset->output->getPathname(), $content);

        $output->writeln('<info>Done</info>');
    }

    $output->writeln('Done');

    $output->writeln('Processing documents...');

    $documents = call_user_func(
        $documentCollector,
        getcwd(),
        $buildDir
    );

    foreach ($documents as $document) {
        $output->write(sprintf(
            '<comment>==> Processing %s -> %s</comment>...',
            $document->input->getRelativePathname(),
            $document->output->getRelativePathname()
        ));

        $content = call_user_func(
            $documentProcessor,
            $buildDir,
            $document,
            $documents
        );

        $filesystem->dumpFile($document->output->getPathname(), $content);

        $output->writeln('<info>Done</info>');
    }

    $output->writeln('Done');

    $output->writeln('Build finished');
}
