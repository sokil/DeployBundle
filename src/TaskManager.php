<?php

namespace Sokil\DeployBundle;

use Sokil\DeployBundle\Task\AbstractTask;
use Sokil\DeployBundle\Task\CommandAwareTaskInterface;
use Sokil\DeployBundle\Task\ProcessRunnerAwareTaskInterface;
use Sokil\DeployBundle\Task\ResourceAwareTaskInterface;
use Sokil\DeployBundle\TaskManager\CommandLocator;
use Sokil\DeployBundle\TaskManager\ProcessRunner;
use Sokil\DeployBundle\TaskManager\ResourceLocator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Sokil\DeployBundle\Exception\TaskNotFoundException;

class TaskManager
{
    /**
     * @var ProcessRunner
     */
    private $processRunner;

    /**
     * @var ResourceLocator
     */
    private $resourceLocator;

    /**
     * @var array<AbstractTask>
     */
    private $tasks = [];

    /**
     * @var Command
     */
    private $consoleCommand;

    /**
     * @var CommandLocator
     */
    private $consoleCommandLocator;

    public function __construct(
        ProcessRunner $processRunner,
        ResourceLocator $resourceLocator,
        CommandLocator $consoleCommandLocator
    ) {
        $this->resourceLocator = $resourceLocator;
        $this->processRunner = $processRunner;
        $this->consoleCommandLocator = $consoleCommandLocator;
    }

    public function configureCommand(Command $command)
    {
        $this->consoleCommand = $command;

        /* @var AbstractTask $task */
        foreach ($this->tasks as $task) {

            $alias = $task->getAlias();

            // configure command parameter to launch task
            $command->addOption(
                $alias,
                null,
                InputOption::VALUE_NONE,
                $task->getDescription()
            );

            // configure command other parameters
            foreach ($task->getCommandOptions() as $optionName => $optionParams) {
                $description = !empty($optionParams['description']) ? $optionParams['description'] : null;
                $defaultValue = !empty($optionParams['default']) ? $optionParams['default'] : null;
                $command->addOption(
                    $alias . '-' . $optionName,
                    null,
                    InputOption::VALUE_OPTIONAL,
                    $description,
                    $defaultValue
                );
            }
        }
    }

    public function addTask(AbstractTask $task)
    {
        $this->tasks[$task->getAlias()] = $task;

        return $this;
    }

    /**
     * @return array<AbstractTask>
     */
    public function getTasks()
    {
        return $this->tasks;
    }

    /**
     * @param string $alias
     * @return AbstractTask
     * @throws TaskNotFoundException
     */
    public function getTask($alias)
    {
        if (!isset($this->tasks[$alias])) {
            throw new TaskNotFoundException('Task with alias "' . $alias . '" not found');
        }

        return $this->tasks[$alias];
    }

    /**
     * Check if all tasks configured to be run
     *
     * @param array $inputOptions
     * @return bool
     */
    private function isAllTasksRequired(array $inputOptions)
    {
        $isRunAllRequired = false;
        if (count(array_intersect_key($this->tasks, array_filter($inputOptions))) === 0) {
            $isRunAllRequired = true;
        }

        return $isRunAllRequired;
    }

    /**
     * Execute tasks
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    public function execute(
        InputInterface $input,
        OutputInterface $output
    ) {
        // get state variables
        $environment = $input->getOption('env');
        $verbosity = $output->getVerbosity();

        // define env variables to run tasks
        putenv('SYMFONY_ENV=' . $environment);

        // check if all task configured to run
        $isRunAllRequired = $this->isAllTasksRequired($input->getOptions());

        // define application
        $this->consoleCommandLocator->setApplication($this->consoleCommand->getApplication());

        /* @var AbstractTask $task */
        foreach ($this->tasks as $taskAlias => $task) {
            if (!$isRunAllRequired && !$input->getOption($taskAlias)) {
                continue;
            }

            // set dependencies
            if ($task instanceof CommandAwareTaskInterface) {
                $task->setCommandLocator($this->consoleCommandLocator);
            }

            if ($task instanceof ResourceAwareTaskInterface) {
                $task->setResourceLocator($this->resourceLocator);
            }

            if ($task instanceof ProcessRunnerAwareTaskInterface) {
                $task->setProcessRunner($this->processRunner);
            }

            // get additional options
            $commandOptions = [];
            foreach ($task->getCommandOptions() as $commandOptionName => $commandOptionParameters) {
                $commandOptions[$commandOptionName] = $input->getOption($taskAlias . '-' . $commandOptionName);
            }

            // run task
            $task->run(
                $commandOptions,
                $environment,
                $verbosity,
                $output
            );
        }
    }
}
