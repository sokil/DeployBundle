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
use Sokil\DeployBundle\TaskManager\CommandLocator;
use Sokil\DeployBundle\TaskManager\ProcessRunner;
use Sokil\DeployBundle\TaskManager\ResourceLocator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Sokil\DeployBundle\Exception\TaskNotFoundException;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

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
                $command->addOption(
                    $alias . '-' . $commandOptionName,
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

        // before all tasks event
        $this->eventDispatcher->dispatch(
            BeforeTasksEvent::name,
            new BeforeTasksEvent($environment, $verbosity, $output)
        );

        /* @var AbstractTask $task */
        foreach ($this->tasks as $taskAlias => $task) {
            if (!$isRunAllRequired && !$input->getOption($taskAlias)) {
                continue;
            }

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
                throw $e;
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
