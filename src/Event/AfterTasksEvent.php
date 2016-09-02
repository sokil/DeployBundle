<?php

namespace Sokil\DeployBundle\Event;

use Symfony\Component\EventDispatcher\Event;

class AfterTasksEvent extends Event
{
    const name = 'afterTasks';
}