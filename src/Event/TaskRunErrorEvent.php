<?php

namespace Sokil\DeployBundle\Event;

use Sokil\DeployBundle\Task\TaskInterface;
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
     * TaskRunErrorEvent constructor.
     * @param TaskInterface $task
     * @param \Exception $exception
     */
    public function __construct(TaskInterface $task, \Exception $exception)
    {
        $this->task = $task;
        $this->exception = $exception;
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
}