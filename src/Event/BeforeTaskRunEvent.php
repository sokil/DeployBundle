<?php

namespace Sokil\DeployBundle\Event;

use Sokil\DeployBundle\Task\TaskInterface;
use Symfony\Component\EventDispatcher\Event;

class BeforeTaskRunEvent extends Event
{
    const name = 'beforeTaskRun';

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