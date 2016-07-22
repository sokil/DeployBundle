<?php

namespace Sokil\DeployBundle\Task;

use Sokil\DeployBundle\TaskManager\ResourceLocator;

interface ResourceAwareTaskInterface
{
    public function setResourceLocator(ResourceLocator $locator);
}