<?php

namespace Sokil\DeployBundle\Task;

use Sokil\DeployBundle\TaskManager\AbstractTask;
use Sokil\DeployBundle\TaskManager\ResourceAwareInterface;
use Sokil\DeployBundle\TaskManager\ResourceLocator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;

class GruntTask extends AbstractTask implements ResourceAwareInterface
{
    /**
     * @var ResourceLocator
     */
    private $resourceLocator;

    public function setResourceLocator(ResourceLocator $locator)
    {
        $this->resourceLocator = $locator;
        return $this;
    }

    public function run(
        callable $input,
        callable $output,
        $environment,
        $verbosity
    ) {
        // get allowed tasks
        $tasks = $this->getOptions('tasks');

        // run task
        foreach ($tasks as $bundleName => $taskList) {
            // get bundle path
            $bundlePath = $this->resourceLocator->locateResource('@' . $bundleName);

            // find path to Gruntfile
            $gruntPath = $bundlePath . 'Gruntfile.js';
            if (!file_exists($gruntPath)) {
                return true;
            }

            $output->writeln('<' . $this->h2Style . '>Execute grunt tasks from ' . $gruntPath . '</>');

            // prepate command
            $command = 'cd ' . $bundlePath . '; grunt --env=' . $environment;

            // configure grunt tasks
            if (is_string($taskList)) {
                $command .= ' ' . $taskList;
            }

            $isSuccessfull = $this->runShellCommand(
                $command,
                function() use ($output) {
                    $output->writeln('Grunt tasks executed successfully');
                },
                function() use ($output) {
                    $output->writeln('<error>Error executing grunt tasks</error>');
                },
                $output
            );

            if (!$isSuccessfull) {
                throw new TaskExecuteException('Error updating bower dependencies for bundle ' . $bundleName);
            }
        }
    }
}