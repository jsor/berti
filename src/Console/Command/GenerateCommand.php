<?php

namespace Berti\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateCommand extends Command
{
    private $generator;

    public function __construct(callable $generator)
    {
        $this->generator = $generator;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('generate')
            ->setDefinition([
                new InputArgument(
                    'build-dir',
                    InputArgument::REQUIRED,
                    'Path to the build directory'
                )
            ])
            ->setDescription('Generates the documentation')
            ->setHelp(<<<EOF
The <info>%command.name%</info> generates the documentation

    <info>php %command.full_name%</info>
EOF
            );
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ): void
    {
        ($this->generator)(
            $input->getArgument('build-dir'),
            $output
        );
    }
}
