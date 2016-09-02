<?php

namespace Sokil\DeployBundle\Event;

use Sokil\DeployBundle\Task\TaskInterface;
use Symfony\Component\EventDispatcher\Event;

class AfterTaskRunEvent extends Event
{
    const name = 'afterTaskRun';

    /**
     * @var TaskInterface
     */
    private $task;

    /**
     * TaskRunErrorEvent constructor.
     * @param TaskInterface $task
     * @param \Exception $exception
     */
    public function __construct(TaskInterface $task)
    {
        $this->task = $task;
    }

    /**
     * @return TaskInterface
     */
    public function getTask()
    {
        return $this->task;
    }
}