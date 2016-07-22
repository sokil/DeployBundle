<?php

namespace Sokil\DeployBundle\Task;

use Sokil\DeployBundle\TaskManager\ProcessRunner;

interface ProcessRunnerAwareTaskInterface
{
    public function setProcessRunner(ProcessRunner $runner);
}