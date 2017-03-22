<?php

namespace Sokil\DeployBundle\Event;

use Sokil\DeployBundle\Task\TaskInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\Event;

class BeforeTaskRunEvent extends Event
{
    const name = 'beforeTaskRun';

    /**
     * @var TaskInterface
     */
    private $task;

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
     * AfterTaskRunEvent constructor.
     * @param TaskInterface $task
     * @param string $environment
     * @param int $verbosity
     * @param OutputInterface $output
     */
    public function __construct(TaskInterface $task, $environment, $verbosity, OutputInterface $output)
    {
        $this->task = $task;
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
