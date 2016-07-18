<?php

namespace Sokil\DeployBundle\TaskManager;

use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\DependencyInjection\ContainerBuilder;

abstract class AbstractTask
{
    const WAIT_PROCESS_EXIT_DELAY = 100000;

    protected $h1Style = 'fg=black;bg=cyan';
    protected $h2Style = 'fg=black;bg=yellow';

    /**
     * @var InputInterface
     */
    protected $input;

    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * @var string
     */
    private $alias;

    /**
     * @var array
     */
    private $options;

    public function __construct(
        $alias,
        array $options
    ) {
        $this->alias = $alias;
        $this->options = $options;
    }

    public function getDescription()
    {
        return 'Description not specified';
    }

    /**
     * @return array command options with parameters
     */
    public function getCommandOptions()
    {
        return [];
    }

    abstract public function run(
        callable $input,
        callable $output,
        $environment,
        $verbosity
    );

    /**
     * @param $command shell command to execute
     * @param callable $doneCallback
     * @param callable $failCallback
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return bool status of command execution
     */
    protected function runShellCommand(
        $command,
        callable $doneCallback,
        callable $failCallback,
        InputInterface $input,
        OutputInterface $output
    ) {
        $verbosity = $output->getVerbosity();
        $environment = $input->getOption('env');

        // show command in debug mode
        if ($verbosity >= OutputInterface::VERBOSITY_VERBOSE) {
            $output->writeln('<info>Command: </info>' . $command);
        }

        // execute command
        $process = new Process(
            $command,
            null, // cwd
            [
                'SYMFONY_ENV' => $environment,
            ],
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
            // fail callback
            call_user_func(
                $failCallback,
                $input,
                $output
            );

            return false;
        }

        // done callback
        call_user_func(
            $doneCallback,
            $input,
            $output
        );

        return true;
    }

    /**
     * @return string
     */
    public function getAlias()
    {
        return $this->alias;
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @param string $name
     * @param mixed|null $default
     * @return mixed|null
     */
    public function getOption($name, $default = null)
    {
        return isset($this->options[$name]) ? $this->options[$name] : $default;
    }
}