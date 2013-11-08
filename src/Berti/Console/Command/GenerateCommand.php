<?php

namespace Berti\Console\Command;

use Berti;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

class GenerateCommand extends Command
{
    private $documentCollector;
    private $documentProcessor;
    private $assetCollector;
    private $assetProcessor;

    public function __construct(callable $documentCollector, callable $documentProcessor, callable $assetCollector, callable $assetProcessor)
    {
        $this->documentCollector = $documentCollector;
        $this->documentProcessor = $documentProcessor;
        $this->assetCollector = $assetCollector;
        $this->assetProcessor = $assetProcessor;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('generate')
            ->setDefinition([
                new InputArgument('build-dir', InputArgument::OPTIONAL, 'Path to the build directory', './.berti/build')
            ])
            ->setDescription('Generates the documentation')
            ->setHelp(<<<EOF
The <info>%command.name%</info> generates the documentation

    <info>php %command.full_name%</info>
EOF
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $filesystem = new Filesystem();

        // ---

        $buildDir = $input->getArgument('build-dir');

        if (!$filesystem->isAbsolutePath($buildDir)) {
            $buildDir = Berti\uri_canonicalizer(getcwd().DIRECTORY_SEPARATOR.$buildDir, DIRECTORY_SEPARATOR);
        }

        $output->writeln(sprintf('<info>Writing build to: %s</info>', $buildDir));

        // ---

        if ($filesystem->exists($buildDir)) {
            $output->write(sprintf('<comment>==> Removing previous build %s</comment>...', $buildDir));
            $filesystem->remove($buildDir);
            $output->writeln('<info>Done</info>');
        }

        $output->writeln('Starting build');

        $output->writeln('Processing assets...');

        $assets = call_user_func(
            $this->assetCollector,
            getcwd(),
            $buildDir
        );

        foreach ($assets as $asset) {
            $output->write(sprintf(
                '<comment>==> Processing %s -> %s</comment>...',
                $asset->input->getRelativePathname(),
                $asset->output->getRelativePathname()
            ));

            $content = call_user_func($this->assetProcessor, $asset, $assets);

            $filesystem->dumpFile($asset->output->getPathname(), $content);

            $output->writeln('<info>Done</info>');
        }

        $output->writeln('Done');

        $output->writeln('Processing documents...');

        $documents = call_user_func(
            $this->documentCollector,
            getcwd(),
            $buildDir
        );

        foreach ($documents as $document) {
            $output->write(sprintf(
                '<comment>==> Processing %s -> %s</comment>...',
                $document->input->getRelativePathname(),
                $document->output->getRelativePathname()
            ));

            $content = call_user_func($this->documentProcessor, $document, $documents);

            $filesystem->dumpFile($document->output->getPathname(), $content);

            $output->writeln('<info>Done</info>');
        }

        $output->writeln('Done');

        $output->writeln('Build finished');
    }
}
