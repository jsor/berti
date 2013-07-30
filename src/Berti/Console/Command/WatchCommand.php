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
    private $paths;

    public function __construct(array $paths)
    {
        $this->paths = $paths;
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

        $paths = $this->paths + array('cwd' => getcwd());

        foreach ($paths as $id => $path) {
            if (!$path) {
                continue;
            }

            $watcher->track($id, $path);

            $watcher->addListener($id, function (FilesystemEvent $event) use ($buildDir, $command, $input, $output) {
                if (0 === strpos($event->getResource(), $buildDir)) {
                    return;
                }

                $output->writeln('');
                $output->writeln(sprintf(
                    '%s <info>%s</info>',
                    $event->getResource(),
                    $this->translateTypeString($event->getTypeString())
                ));
                $output->writeln('');

                $command->run($input, $output);
            });
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
