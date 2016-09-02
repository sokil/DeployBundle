<?php

namespace Sokil\DeployBundle\Event;

use Symfony\Component\EventDispatcher\Event;

class BeforeTasksEvent extends Event
{
    const name = 'beforeTasks';
}