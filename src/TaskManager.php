<?php

namespace Sokil\DeployBundle;

use Sokil\DeployBundle\Event\AfterTaskRunEvent;
use Sokil\DeployBundle\Event\AfterTasksEvent;
use Sokil\DeployBundle\Event\BeforeTaskRunEvent;
use Sokil\DeployBundle\Event\BeforeTasksEvent;
use Sokil\DeployBundle\Event\TaskRunErrorEvent;
use Sokil\DeployBundle\Task\AbstractTask;
use Sokil\DeployBundle\Task\CommandAwareTaskInterface;
use Sokil\DeployBundle\Task\ProcessRunnerAwareTaskInterface;
use Sokil\DeployBundle\Task\ResourceAwareTaskInterface;
use Sokil\DeployBundle\Task\TaskInterface;
use Sokil\DeployBundle\TaskManager\CommandLocator;
use Sokil\DeployBundle\TaskManager\ProcessRunner;
use Sokil\DeployBundle\TaskManager\ResourceLocator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Sokil\DeployBundle\Exception\TaskNotFoundException;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class TaskManager
{
    const DEFAULT_TASK_BUNDLE_NAME = 'default';

    /**
     * @var array
     */
    private $tasksConfiguration;

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
     * List of task bundles, where jey is bundle name and value is list of task names in bundle
     *
     * @var array
     */
    private $taskBundles = [];

    /**
     * @var Command
     */
    private $consoleCommand;

    /**
     * @var CommandLocator
     */
    private $consoleCommandLocator;

    /**
     * @var EventDispatcher
     */
    private $eventDispatcher;

    /**
     * @param array $tasksConfiguration
     * @param ProcessRunner $processRunner
     * @param ResourceLocator $resourceLocator
     * @param CommandLocator $consoleCommandLocator
     */
    public function __construct(
        array $tasksConfiguration,
        ProcessRunner $processRunner,
        ResourceLocator $resourceLocator,
        CommandLocator $consoleCommandLocator
    ) {
        $this->tasksConfiguration = $tasksConfiguration;
        $this->resourceLocator = $resourceLocator;
        $this->processRunner = $processRunner;
        $this->consoleCommandLocator = $consoleCommandLocator;
        $this->eventDispatcher = new EventDispatcher();
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
            foreach ($task->getCommandOptionDefinitions() as $commandOptionName => $commandOptionDefinition) {
                $description = !empty($commandOptionDefinition['description'])
                    ? $commandOptionDefinition['description']
                    : null;

                $defaultValue = !empty($commandOptionDefinition['default'])
                    ? $commandOptionDefinition['default']
                    : null;

                $mode = !empty($commandOptionDefinition['mode'])
                    ? $commandOptionDefinition['mode']
                    : InputOption::VALUE_OPTIONAL;

                $shortcut = !empty($commandOptionDefinition['shortcut'])
                    ? $commandOptionDefinition['shortcut']
                    : null;

                $command->addOption(
                    $alias . '-' . $commandOptionName,
                    $shortcut,
                    $mode,
                    $description,
                    $defaultValue
                );
            }
        }

        // configure bundles of tasks
        foreach ($this->taskBundles as $taskBundleName => $taskNames) {
            $command->addOption(
                $taskBundleName,
                null,
                InputOption::VALUE_NONE,
                'Task bundle for tasks "' . implode('","', $taskNames) . '"'
            );
        }
    }

    public function addTask(AbstractTask $task)
    {
        if (!isset($this->tasksConfiguration[$task->getAlias()])) {
            throw new \Exception(sprintf('Task manager has no configuration for %s', $task->getAlias()));
        }

        // register event subscriber
        if ($task instanceof EventSubscriberInterface) {
            $this->eventDispatcher->addSubscriber($task);
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

        // set configuration
        $task->configure($this->tasksConfiguration[$task->getAlias()]);

        // register task
        $this->tasks[$task->getAlias()] = $task;

        return $this;
    }

    public function addTaskBundle($bundleName, array $taskNameList)
    {
        $this->taskBundles[$bundleName] = $taskNameList;
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
     *
     * @return AbstractTask
     *
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
     * Bundle is an array of tasks and other task bundles
     * Convert it to plain task list
     *
     * @param array $taskNameList
     *
     * @return array
     */
    private function normalizeTaskList(array $taskNameList)
    {
        $normalizedTaskNames = [];

        foreach ($taskNameList as $taskName) {
            if (isset($this->taskBundles[$taskName])) {
                // taskName belongs to bundle
                $normalizedTaskNames = array_merge(
                    $normalizedTaskNames,
                    $this->normalizeTaskList($this->taskBundles[$taskName])
                );
            } else {
                // taskName belongs to task
                $normalizedTaskNames[] = $taskName;
            }
        }

        return $normalizedTaskNames;
    }

    /**
     * Get task aliases, required to run, from cli options
     *
     * @param InputInterface $input
     * @return TaskInterface[]
     */
    private function getTasksFromCliOptions(InputInterface $input)
    {
        $inputOptionNames = array_keys(array_filter($input->getOptions()));

        // get task names from cli
        $taskNames = array_merge(
            // tasks
            array_intersect(
                array_keys($this->tasks),
                $inputOptionNames
            ),
            // tasks in bundles
            $this->normalizeTaskList(
                array_intersect(
                    array_keys($this->taskBundles),
                    $inputOptionNames
                )
            )
        );

        // no tasks specified in cli - run default bundle
        // in always contain only tasks
        if (empty($taskNames)) {
            $taskNames = $this->taskBundles[self::DEFAULT_TASK_BUNDLE_NAME];
        }

        // find tasks
        $tasks = array_intersect_key(
            $this->tasks,
            array_fill_keys($taskNames, true)
        );

        return $tasks;
    }

    /**
     * Execute tasks
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @throws \Exception
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

        // get list of task aliases to run, specified in cli options
        $taskAliasesToRun = $this->getTasksFromCliOptions($input);

        // define application
        $this->consoleCommandLocator->setApplication($this->consoleCommand->getApplication());

        // before all tasks event
        $this->eventDispatcher->dispatch(
            BeforeTasksEvent::NAME,
            new BeforeTasksEvent($environment, $verbosity, $output)
        );

        /* @var AbstractTask $task */
        foreach ($taskAliasesToRun as $taskAlias => $task) {
            // get additional options
            $commandOptions = [];
            foreach ($task->getCommandOptionDefinitions() as $commandOptionName => $commandOptionDefinition) {
                $commandOptions[$commandOptionName] = $input->getOption($taskAlias . '-' . $commandOptionName);
            }

            // before run task
            $this->eventDispatcher->dispatch(
                BeforeTaskRunEvent::NAME,
                new BeforeTaskRunEvent($task, $environment, $verbosity, $output)
            );

            // run task
            try {
                $task->run(
                    $commandOptions,
                    $environment,
                    $verbosity,
                    $output
                );
            } catch (\Exception $exception) {
                $this->eventDispatcher->dispatch(
                    TaskRunErrorEvent::NAME,
                    new TaskRunErrorEvent($task, $exception, $environment, $verbosity, $output)
                );
                throw $exception;
            }

            // after run task
            $this->eventDispatcher->dispatch(
                AfterTaskRunEvent::NAME,
                new AfterTaskRunEvent($task, $environment, $verbosity, $output)
            );
        }

        // after all tasks event
        $this->eventDispatcher->dispatch(
            AfterTasksEvent::NAME,
            new AfterTasksEvent($environment, $verbosity, $output)
        );
    }
}
