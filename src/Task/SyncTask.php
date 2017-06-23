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
    public function configure(array $options)
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
     * @param array $commandOptions cli options
     * @param string $environment
     * @param int $verbosity
     * @param OutputInterface $output
     *
     * @throws TaskConfigurationValidateException
     */
    public function run(
        array $commandOptions,
        $environment,
        $verbosity,
        OutputInterface $output
    ) {
        // build command list
        $commands = [];
        foreach ($this->rules as $ruleName => $rule) {
            // source
            $source = '.';
            if (!empty($rule['src'])) {
                $source = $rule['src'];
                unset($rule['src']);
            }

            // destination
            if (empty($rule['dest'])) {
                throw new TaskConfigurationValidateException(sprintf('Destination not specified for %s', $ruleName));
            }

            $destinationList = (array)$rule['dest'];
            unset($rule['dest']);

            // build command
            $command = ['rsync -a'];
            foreach ($rule as $ruleOptionName => $ruleOptionValue) {
                if (is_bool($ruleOptionValue)) {
                    // flag
                    if ($ruleOptionValue === true) {
                        $command[] = '--' . $ruleOptionName;
                    }
                } elseif (is_array($ruleOptionValue)) {
                    // array argument
                    foreach ($ruleOptionValue as $commandOptionValueElement) {
                        $command[] = '--' . $ruleOptionName . ' ' . $commandOptionValueElement;
                    }
                } else {
                    // scalar argument
                    $command[] = '--' . $ruleOptionName . ' ' . $ruleOptionValue;
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
