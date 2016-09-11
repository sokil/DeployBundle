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
    private $taskBundles;

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

    public function __construct(
        ProcessRunner $processRunner,
        ResourceLocator $resourceLocator,
        CommandLocator $consoleCommandLocator
    ) {
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
            foreach ($task->getCommandOptions() as $commandOptionName => $commandOptionParameters) {
                $description = !empty($commandOptionParameters['description']) ? $commandOptionParameters['description'] : null;
                $defaultValue = !empty($commandOptionParameters['default']) ? $commandOptionParameters['default'] : null;
                $mode = !empty($commandOptionParameters['mode']) ? $commandOptionParameters['mode'] : InputOption::VALUE_OPTIONAL;
                $command->addOption(
                    $alias . '-' . $commandOptionName,
                    null,
                    $mode,
                    $description,
                    $defaultValue
                );
            }
        }

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
        // register task
        $this->tasks[$task->getAlias()] = $task;

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
     * Get task aliases, required to run, from cli options
     *
     * @param InputInterface $input
     * @return TaskInterface[]
     */
    private function getTasksFromCliOptions(InputInterface $input)
    {
        // check if concrete tasks configured to run
        $tasksInCliOptions = array_intersect_key(
            $this->tasks,
            array_filter($input->getOptions())
        );

        if (count($tasksInCliOptions) > 0) {
            return $tasksInCliOptions;
        }

        // no concrete tasks to run - find task bundles
        $taskBundlesInCliOptions = array_intersect_key(
            $this->taskBundles,
            array_filter($input->getOptions())
        );

        if (count($taskBundlesInCliOptions) === 0) {
            // no task bundles specified in cli - run default bundle
            $taskBundlesInCliOptions = [
                TaskManager::DEFAULT_TASK_BUNDLE_NAME => $this->taskBundles[TaskManager::DEFAULT_TASK_BUNDLE_NAME],
            ];
        }

        $taskAliasesToRun = array_unique(call_user_func_array('array_merge', $taskBundlesInCliOptions));

        $tasksInCliOptions = array_intersect_key(
            $this->tasks,
            array_flip($taskAliasesToRun)
        );

        return $tasksInCliOptions;
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

        // get list of task aliases to run, specified in cli options
        $taskAliasesToRun = $this->getTasksFromCliOptions($input);

        // define application
        $this->consoleCommandLocator->setApplication($this->consoleCommand->getApplication());

        // before all tasks event
        $this->eventDispatcher->dispatch(
            BeforeTasksEvent::name,
            new BeforeTasksEvent($environment, $verbosity, $output)
        );

        /* @var AbstractTask $task */
        foreach ($taskAliasesToRun as $taskAlias => $task) {
            // get additional options
            $commandOptions = [];
            foreach ($task->getCommandOptions() as $commandOptionName => $commandOptionParameters) {
                $commandOptions[$commandOptionName] = $input->getOption($taskAlias . '-' . $commandOptionName);
            }

            // before run task
            $this->eventDispatcher->dispatch(
                BeforeTaskRunEvent::name,
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
                    TaskRunErrorEvent::name,
                    new TaskRunErrorEvent($task, $exception, $environment, $verbosity, $output));
                throw $exception;
            }

            // after run task
            $this->eventDispatcher->dispatch(
                AfterTaskRunEvent::name,
                new AfterTaskRunEvent($task, $environment, $verbosity, $output)
            );
        }

        // after all tasks event
        $this->eventDispatcher->dispatch(
            AfterTasksEvent::name,
            new AfterTasksEvent($environment, $verbosity, $output)
        );
    }
}
