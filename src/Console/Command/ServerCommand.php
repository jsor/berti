<?php

namespace Berti\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ServerCommand extends Command
{
    private $server;

    public function __construct(callable $server)
    {
        $this->server = $server;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('server')
            ->setDefinition([
                new InputArgument(
                    'build-dir',
                    InputArgument::REQUIRED,
                    'Path to the build directory'
                ),
                new InputArgument('address', InputArgument::OPTIONAL, 'Address:port', '127.0.0.1:8000')
            ])
            ->setDescription('Runs PHP\'s built-in web server')
            ->setHelp(<<<EOF
The <info>%command.name%</info> runs PHP's built-in web server and generates documents on-the-fly.

    <info>php %command.full_name%</info>
EOF
            );
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ): void
    {
        ($this->server)(
            getcwd(),
            $input->getArgument('build-dir'),
            $output,
            $input->getArgument('address')
        );
    }
}
