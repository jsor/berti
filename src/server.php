<?php

namespace Berti;

use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\ProcessBuilder;

function server(
    OutputInterface $output,
    array $env = [],
    string $address = '127.0.0.1:8000'
): int
{
    $router = realpath(__DIR__ . '/../scripts/router.php');

    $finder = new PhpExecutableFinder();

    if (false === $binary = $finder->find()) {
        $output->writeln('Unable to find PHP binary to run server.');

        return 1;
    }

    $builder = new ProcessBuilder([
        $binary,
        '-d',
        'variables_order=EGPCS',
        '-S',
        $address,
        $router
    ]);

    $builder->inheritEnvironmentVariables();
    $builder->addEnvironmentVariables($env);
    $builder->setTimeout(null);
    $process = $builder->getProcess();
    $callback = null;

    try {
        $process->setTty(true);
    } catch (\RuntimeException $e) {
        $callback = function ($type, $buffer) use ($output) {
            if (Process::ERR === $type && $output instanceof ConsoleOutputInterface) {
                $output = $output->getErrorOutput();
            }
            $output->write($buffer, false, OutputInterface::OUTPUT_RAW);
        };
    }

    $process->run($callback);

    if (!$process->isSuccessful()) {
        $output->writeln('Built-in server terminated unexpectedly.');
    }

    return $process->getExitCode();
}
