<?php

namespace Sokil\DeployBundle\Task;

use Sokil\DeployBundle\TaskManager\CommandLocator;

interface CommandAwareTaskInterface
{
    public function setCommandLocator(CommandLocator $locator);
}