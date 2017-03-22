<?php

namespace Sokil\DeployBundle\TaskManager;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\RuntimeException;

class ProcessRunner
{
    const WAIT_PROCESS_EXIT_DELAY = 100000;
    const WAIT_PROCESS_CHECKSTATUS_DELAY = 300000;

    /**
     * Create instane of process
     *
     * @param string         $commandline The command line to run
     * @param string|null    $cwd         The working directory or null to use the working dir of the current PHP process
     * @param array|null     $env         The environment variables or null to use the same environment as the current PHP process
     * @param mixed|null     $input       The input as stream resource, scalar or \Traversable, or null for no input
     * @param int|float|null $timeout     The timeout in seconds or null to disable
     * @param array          $options     An array of options for proc_open
     *
     * @throws RuntimeException When proc_open is not installed
     * @return Process
     */
    protected function createProcess(
        $commandline,
        $cwd = null,
        array $env = null,
        $input = null,
        $timeout = 60,
        array $options = array()
    ) {
        return new Process(
            $commandline,
            $cwd,
            $env,
            $input,
            $timeout,
            $options
        );
    }

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

        // create command
        $process = $this->createProcess(
            $command
        );

        // execute command
        $process->start();

        // run standard output
        if ($verbosity >= OutputInterface::VERBOSITY_VERBOSE) {
            while ($process->isRunning()) {
                $output->write($process->getIncrementalOutput());
                $output->write($process->getIncrementalErrorOutput());
            }
        }

        // wait exitcode
        while ($process->getExitCode() === null) {
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

    public function parallelRun(
        array $commandList,
        $environment,
        $verbosity,
        OutputInterface $output
    ) {

        // create processes
        $processList = [];
        foreach ($commandList as $command) {
            // create process
            $process = $this->createProcess(
                $command
            );
            // execute command
            $process->start();
            $pid = $process->getPid();
            // add to process pool
            $processList[$pid] = $process;
            // show command in debug mode
            if ($verbosity >= OutputInterface::VERBOSITY_VERBOSE) {
                $output->writeln('<info>Command # ' . $pid . ': </info>' . $command);
            }
        }

        // wait status
        $isSuccessful = true;

        while (count($processList) > 0) {
            foreach ($processList as $pid => $process) {
                // if process still running - check next process
                if ($process->isRunning()) {
                    usleep(self::WAIT_PROCESS_CHECKSTATUS_DELAY);
                    continue;
                }

                // wait exit code
                while ($process->getExitCode() === null) {
                    usleep(self::WAIT_PROCESS_EXIT_DELAY);
                }

                // show exit status
                if ($process->isSuccessful()) {
                    // show success message
                    $output->writeln('Process with pid ' . $pid . ' executed successfully');
                    // status
                    $isSuccessful &= true;
                } else {
                    // exit code
                    $output->writeln('Process ' . $pid . ' exited with error #' . $process->getExitCode() . ' ' . $process->getExitCodeText());
                    // render error output
                    $output->writeln($process->getErrorOutput());
                    // status
                    $isSuccessful &= false;
                }

                // remove finished tasks
                unset($processList[$pid]);
            }
        }

        return $isSuccessful;
    }
}
