<?php

namespace Sokil\DeployBundle\Event;

use Sokil\DeployBundle\Task\TaskInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\Event;

class TaskRunErrorEvent extends Event
{
    const name = 'taskRunError';

    /**
     * @var TaskInterface
     */
    private $task;

    /**
     * @var \Exception
     */
    private $exception;

    /**
     * @var string
     */
    private $environment;

    /**
     * @var int
     */
    private $verbosity;

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * TaskRunErrorEvent constructor.
     * @param TaskInterface $task
     * @param \Exception $exception
     * @param string $environment
     * @param int $verbosity
     * @parap OutputInterface $output
     */
    public function __construct(
        TaskInterface $task,
        \Exception $exception,
        $environment,
        $verbosity,
        OutputInterface $output
    ) {
        $this->task = $task;
        $this->exception = $exception;
        $this->environment = $environment;
        $this->verbosity = $verbosity;
        $this->output = $output;
    }

    /**
     * @return TaskInterface
     */
    public function getTask()
    {
        return $this->task;
    }

    /**
     * @return \Exception
     */
    public function getException()
    {
        return $this->exception;
    }

    /**
     * @return string
     */
    public function getEnvironment()
    {
        return $this->environment;
    }

    /**
     * @return int
     */
    public function getVerbosity()
    {
        return $this->verbosity;
    }

    /**
     * @return OutputInterface
     */
    public function getOutput()
    {
        return $this->output;
    }
}