<?php

namespace Berti;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

function generator(
    callable $documentCollector,
    callable $documentProcessor,
    callable $assetCollector,
    callable $assetProcessor,
    string $srcDir,
    string $buildDir,
    OutputInterface $output
): void
{
    $filesystem = new Filesystem();

    // ---

    if (!$filesystem->isAbsolutePath($buildDir)) {
        $buildDir = uri_canonicalizer(
            $srcDir . DIRECTORY_SEPARATOR.$buildDir,
            DIRECTORY_SEPARATOR
        );
    }

    $output->writeln(sprintf('<info>Writing build to: %s</info>', $buildDir));

    // ---

    $output->writeln('Starting build');

    $output->writeln('Processing assets...');

    $assets = $assetCollector(
        $srcDir,
        $buildDir
    );

    foreach ($assets as $asset) {
        $output->write(sprintf(
            '<comment>==> Processing %s -> %s</comment>...',
            $asset->input->getRelativePathname(),
            $asset->output->getRelativePathname()
        ));

        $content = $assetProcessor($asset, $assets);

        $filesystem->dumpFile($asset->output->getPathname(), $content);

        $output->writeln('<info>Done</info>');
    }

    $output->writeln('Done');

    $output->writeln('Processing documents...');

    $documents = $documentCollector(
        $srcDir,
        $buildDir
    );

    foreach ($documents as $document) {
        $output->write(sprintf(
            '<comment>==> Processing %s -> %s</comment>...',
            $document->input->getRelativePathname(),
            $document->output->getRelativePathname()
        ));

        $content = $documentProcessor(
            $buildDir,
            $document,
            $documents,
            $assets
        );

        $filesystem->dumpFile($document->output->getPathname(), $content);

        $output->writeln('<info>Done</info>');
    }

    $output->writeln('Done');

    $output->writeln('Build finished');
}
