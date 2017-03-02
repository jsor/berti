<?php

namespace Berti\Console;

use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Application extends BaseApplication
{
    const VERSION = '1.0.0';

    private $commands;

    private $workingDir;
    private $configFile;

    public function __construct(array $commands = null)
    {
        $this->commands = $commands;

        parent::__construct('Berti', self::VERSION);
    }

    public function __invoke($workingDir, $configFile, array $argv = null)
    {
        $this->workingDir = $workingDir;
        $this->configFile = $configFile;

        $this->setAutoExit(false);

        return $this->run(new ArgvInput($argv));
    }

    public function doRun(InputInterface $input, OutputInterface $output)
    {
        $startTime = microtime(true);

        if ($this->workingDir) {
            $workingDir = realpath($this->workingDir);

            if ($workingDir) {
                $output->writeln(sprintf(
                    '<info>Using working directory: %s</info>',
                    $workingDir
                ));
            } else {
                throw new \RuntimeException('Invalid working directory specified.');
            }
        }

        if ($this->configFile) {
            $configFile = realpath($this->configFile);

            if ($configFile) {
                $output->writeln(sprintf(
                   '<info>Using config file: %s</info>',
                   $configFile
               ));
            } elseif ('./berti.config.php' !== $this->configFile) {
                throw new \RuntimeException('Invalid config file specified.');
            }
        }

        $result = parent::doRun($input, $output);

        $output->writeln('<info>Memory usage: '.round(memory_get_usage() / 1024 / 1024, 2).'MB (peak: '.round(memory_get_peak_usage() / 1024 / 1024, 2).'MB), time: '.round(microtime(true) - $startTime, 2).'s');

        return $result;
    }

    protected function getDefaultCommands()
    {
        $commands = parent::getDefaultCommands();

        if ($this->commands) {
            $commands = array_merge($commands, $this->commands);
        }

        return $commands;
    }

    protected function getDefaultInputDefinition()
    {
        $definition = parent::getDefaultInputDefinition();
        $definition->addOption(new InputOption('--working-dir', '-d', InputOption::VALUE_REQUIRED, 'If specified, use the given directory as working directory.'));
        $definition->addOption(new InputOption('--config', '-c', InputOption::VALUE_REQUIRED, 'If specified, read additional configuration from the given config file.', './berti.config.php'));

        return $definition;
    }
}
