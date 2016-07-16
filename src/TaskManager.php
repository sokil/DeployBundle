<?php

namespace Sokil\DeployBundle;

use Sokil\DeployBundle\TaskManager\BundleTaskInterface;
use Sokil\DeployBundle\TaskManager\ResourceLocator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Sokil\DeployBundle\TaskManager\AbstractTask;
use Sokil\DeployBundle\Exception\TaskNotFoundException;

class TaskManager
{
    /**
     * @var array<AbstractTask>
     */
    private $tasks;

    /**
     * @var ResourceLocator
     */
    private $resourceLocator;

    public function __construct(ResourceLocator $resourceLocator)
    {
        $this->resourceLocator = $resourceLocator;
    }

    public function configureCommand(Command $command)
    {
        /* @var AbstractTask $task */
        foreach ($this->tasks as $task) {

            // configure command parameter to launch task
            $command->addOption(
                $task->getAlias(),
                null,
                InputOption::VALUE_NONE,
                $task->getDescription()
            );

            // configure command other parameters
            $task->configureCommand($command);
        }
    }

    public function addTask(AbstractTask $task)
    {
        if ($task instanceof BundleTaskInterface) {
            $bundleNameList = [];
            $task->setBundles($this->getBundlePathList($bundleNameList));
        }

        $this->tasks[$task->getAlias()] = $task;

        return $this;
    }

    public function getTasks()
    {
        return $this->tasks;
    }

    public function getTask($alias)
    {
        if (!isset($this->tasks[$alias])) {
            throw new TaskNotFoundException('Task with alias "' . $alias . '" not found');
        }

        return $this->tasks[$alias];
    }

    /**
     * Get pathes to bundles by list of bundle names
     * @param array $bundleNameList
     * @return array
     */
    private function getBundlePathList(array $bundleNameList)
    {
        $bundlePathList = [];
        foreach ($bundleNameList as $bundleName) {
            $bundlePath = $this->resourceLocator->locateResource('@' . $bundleName);
            $bundlePathList[$bundleName] = $bundlePath;
        }

        return $bundlePathList;
    }

    public function execute(
        InputInterface $input,
        OutputInterface $output
    ) {
        $isRunAllRequired = false;
        if (count(array_intersect_key($input->getOptions(), $this->tasks)) === 0) {
            $isRunAllRequired = true;
        }

        /* @var AbstractTask $task */
        foreach ($this->tasks as $taskAlias => $task) {
            if (!$isRunAllRequired && !$input->getOption($taskAlias)) {
                continue;
            }

            $task->run($input, $output);
        }
    }
}