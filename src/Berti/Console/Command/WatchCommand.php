<?php

namespace Berti\Console\Command;

use Berti;
use Lurker\Event\FilesystemEvent;
use Lurker\ResourceWatcher;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

class WatchCommand extends Command
{
    private $documentFinder;
    private $themePath;

    public function __construct(callable $documentFinder, $themePath = null)
    {
        $this->documentFinder = $documentFinder;
        $this->themePath = $themePath;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('watch')
            ->setDefinition([
                new InputArgument('build-dir', InputArgument::OPTIONAL, 'Path to the build directory', './berti-build')
            ])
            ->setDescription('Watches for file changes and runs the generate command')
            ->setHelp(<<<EOF
The <info>%command.name%</info> watches paths for generation

    <info>php %command.full_name%</info>
EOF
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $filesystem = new Filesystem();

        $buildDir = $input->getArgument('build-dir');

        if (!$filesystem->isAbsolutePath($buildDir)) {
            $buildDir = Berti\uri_canonicalizer(getcwd().DIRECTORY_SEPARATOR.$buildDir, DIRECTORY_SEPARATOR);
        }

        $output->writeln('Watching...');

        $watcher = new ResourceWatcher;

        $command = $this->getApplication()->find('generate');
        $arguments = [
            'build-dir' => $input->getArgument('build-dir')
        ];
        $input = new ArrayInput($arguments);

        $build = function ($file, $type) use ($command, $input, $output) {
            $output->writeln('');
            $output->writeln(sprintf(
                '%s <info>%s</info>',
                $file,
                $this->translateTypeString($type)
            ));
            $output->writeln('');

            $command->run($input, $output);
        };

        $track = function($id, $path) use ($watcher, $build) {
            $watcher->track($id, $path, FilesystemEvent::MODIFY | FilesystemEvent::DELETE);
            $watcher->addListener($id, function (FilesystemEvent $event) use ($build) {
                $build($event->getResource(), $event->getTypeString());
            });
        };

        $documents = call_user_func(
            $this->documentFinder,
            getcwd()
        );

        foreach ($documents as $document) {
            $track($document->getRelativePathname(), $document->getPathname());
        }

        $watcher->track('new', getcwd(), FilesystemEvent::CREATE);
        $watcher->addListener('new', function (FilesystemEvent $event) use ($track, $build) {
            $documents = call_user_func(
                $this->documentFinder,
                getcwd()
            );

            foreach ($documents as $document) {
                if ($document->getPathname() !== (string) $event->getResource()) {
                    continue;
                }

                $track($document->getRelativePathname(), $document->getPathname());
                $build($event->getResource(), $event->getTypeString());
                break;
            }
        });

        if ($this->themePath) {
            $track('theme.path', $this->themePath);
        }

        $watcher->start();

        $output->writeln('Stopped');
    }

    protected function translateTypeString($typeString)
    {
        switch ($typeString) {
            case 'modify':
                return 'modified';
            default:
                return $typeString . 'd';
        }
    }
}
