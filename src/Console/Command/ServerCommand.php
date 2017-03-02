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

    protected function configure()
    {
        $this
            ->setName('server')
            ->setDefinition([
                new InputArgument('address', InputArgument::OPTIONAL, 'Address:port', '127.0.0.1:8000')
            ])
            ->setDescription('Runs PHP\'s built-in web server')
            ->setHelp(<<<EOF
The <info>%command.name%</info> runs PHP's built-in web server and generates documents on-the-fly.

    <info>php %command.full_name%</info>
EOF
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $env = [];

        if ($config = $input->getArgument('config')) {
            $env['BERTI_CONFIG'] = $config;
        }

        ($this->server)(
            $output,
            $env,
            $input->getArgument('address')
        );
    }
}
