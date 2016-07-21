<?php

namespace Sokil\DeployBundle\TaskManager;

interface ResourceAwareTaskInterface extends TaskInterface
{
    public function setResourceLocator(ResourceLocator $locator);
}