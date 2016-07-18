<?php

namespace Sokil\DeployBundle\TaskManager;

interface ResourceAwareInterface
{
    public function setResourceLocator(ResourceLocator $locator);
}