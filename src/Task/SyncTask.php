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
use Sokil\DeployBundle\Task\SyncTask\SyncCommand;
use Sokil\DeployBundle\TaskManager\ProcessRunner;
use Symfony\Component\Console\Output\OutputInterface;

class SyncTask extends AbstractTask implements ProcessRunnerAwareTaskInterface
{
    /**
     * @var array
     */
    private $commands = [];

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
        // configure commands
        foreach ($options as $commandName => $commandDefinition) {
            $command = new SyncCommand();

            if (!empty($commandDefinition['source'])) {
                $command->setSource($commandDefinition['source']);
            }

            if (!empty($commandDefinition['target'])) {
                $command->setTarget((array)$commandDefinition['target']);
            }

            if (!empty($commandDefinition['exclude'])) {
                $command->setExclude((array)$commandDefinition['exclude']);
            }

            if (!empty($commandDefinition['include'])) {
                $command->setInclude((array)$commandDefinition['include']);
            }

            if (isset($commandDefinition['deleteExtraneousFiles'])) {
                $command->setDeleteExtraneousFiles((bool)$commandDefinition['deleteExtraneousFiles']);
            }

            $this->commands[$commandName] = $command;
        }

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
        $commands = [];

        /**
         * @var string $commandName
         * @var SyncCommand $command
         */
        foreach ($this->commands as $commandName => $command) {
            foreach ($command->getNext() as $commandString) {
                $commands[] = $commandString;
            }
        }

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
