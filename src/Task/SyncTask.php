<?php

/**
 * This file is part of the DeployBundle package.
 *
 * (c) Dmytro Sokil <dmytro.sokil@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sokil\DeployBundle\Task;

use Sokil\DeployBundle\Exception\TaskConfigurationValidateException;
use Sokil\DeployBundle\TaskManager\ProcessRunner;
use Symfony\Component\Console\Output\OutputInterface;

class SyncTask extends AbstractTask implements ProcessRunnerAwareTaskInterface
{
    /**
     * @var array
     */
    private $rules;

    /**
     * @var int
     */
    private $parallelProcessesCount = 1;

    /**
     * @var ProcessRunner
     */
    private $processRunner;

    /**
     * Get console command description
     * @return mixed
     */
    public function getDescription()
    {
        return 'Sync files between servers';
    }

    /**
     * Prepare task options configured in bundle`s config: check values and set default values
     *
     * @param array $options configuration
     *
     * @throws TaskConfigurationValidateException
     */
    protected function configure(array $options)
    {
        // set rules
        if (empty($options['rules']) || !is_array($options['rules'])) {
            throw new TaskConfigurationValidateException('Rules for sync not configured');
        }

        $this->rules = $options['rules'];

        // set parallel
        if (!empty($options['parallel']) && is_numeric($options['parallel'])) {
            $this->parallelProcessesCount = $options['parallel'];
        }
    }

    /**
     * @param ProcessRunner $runner
     */
    public function setProcessRunner(ProcessRunner $runner)
    {
        $this->processRunner = $runner;
    }

    /**
     * Run task
     *
     * @param array $commandOptions
     * @param $environment
     * @param $verbosity
     * @param OutputInterface $output
     */
    public function run(
        array $commandOptions,
        $environment,
        $verbosity,
        OutputInterface $output
    ) {
        // build command list
        $commands = [];
        foreach ($this->rules as $ruleName => $commandOptions) {
            // source
            $source = '.';
            if (!empty($commandOptions['src'])) {
                $source = $commandOptions['src'];
                unset($commandOptions['src']);
            }

            // destination
            if (empty($commandOptions['dest'])) {
                throw new TaskConfigurationValidateException(sprintf('Destination not specified for %s', $ruleName));
            }

            $destinationList = (array)$commandOptions['dest'];
            unset($commandOptions['dest']);

            // build command
            $command = ['rsync -a'];
            foreach ($commandOptions as $commandOptionName => $commandOptionValue) {
                if (is_bool($commandOptionValue)) {
                    // flag
                    if ($commandOptionValue === true) {
                        $command[] = '--' . $commandOptionName;
                    }
                } elseif (is_array($commandOptionValue)) {
                    // array argument
                    foreach ($commandOptionValue as $commandOptionValueElement) {
                        $command[] = '--' . $commandOptionName . ' ' . $commandOptionValueElement;
                    }
                } else {
                    // scalar argument
                    $command[] = '--' . $commandOptionName . ' ' . $commandOptionValue;
                }
            }

            // build command list
            foreach ($destinationList as $destination) {
                $commands[] = implode(' ', $command) . ' ' . $source . ' ' . $destination;
            }
        }

        // run commands
        if ($this->parallelProcessesCount > 1) {
            $commandChunks = array_chunk($commands, $this->parallelProcessesCount);
            foreach ($commandChunks as $commandChunk) {
                $this->processRunner->parallelRun(
                    $commandChunk,
                    $environment,
                    $verbosity,
                    $output
                );
            }
        } else {
            foreach ($commands as $commandString) {
                $this->processRunner->run(
                    $commandString,
                    $environment,
                    $verbosity,
                    $output
                );
            }
        }
    }
}
