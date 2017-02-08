<?php

namespace Berti\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class WatchCommand extends Command
{
    private $watcher;

    public function __construct(callable $watcher)
    {
        $this->watcher = $watcher;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('watch')
            ->setDefinition([
                new InputArgument('build-dir', InputArgument::OPTIONAL, 'Path to the build directory', './.berti/build')
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
        call_user_func(
            $this->watcher,
            $input->getArgument('build-dir'),
            $output
        );
    }
}
