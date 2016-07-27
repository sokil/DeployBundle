<?php

namespace Sokil\DeployBundle\TaskManager;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class ProcessRunner
{
    const WAIT_PROCESS_EXIT_DELAY = 100000;

    /**
     * @param string $command shell command to execute
     * @param string $environment
     * @param string $verbosity
     * @param callable $doneCallback
     * @param callable $failCallback
     * @param OutputInterface $output
     *
     * @return bool status of command execution
     */
    public function run(
        $command,
        $environment,
        $verbosity,
        OutputInterface $output
    ) {
        // show command in debug mode
        if ($verbosity >= OutputInterface::VERBOSITY_VERBOSE) {
            $output->writeln('<info>Command: </info>' . $command);
        }

        // execute command
        $process = new Process(
            $command,
            null, // cwd
            null, // env
            null, // input
            null  // timeout
        );

        $process->start();

        // run standard output
        if ($verbosity >= OutputInterface::VERBOSITY_VERBOSE) {
            while ($process->isRunning()) {
                $output->write($process->getIncrementalOutput());
                $output->write($process->getIncrementalErrorOutput());
            }
        }

        // wait exitcode
        while($process->getExitCode() === null) {
            usleep(self::WAIT_PROCESS_EXIT_DELAY);
        }

        // state handling
        if (!$process->isSuccessful()) {
            // render error output
            $output->writeln($process->getErrorOutput());
            // exit code
            $output->writeln($process->getExitCodeText());
            return false;
        }

        return true;
    }
}