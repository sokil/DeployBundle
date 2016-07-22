<?php

namespace Sokil\DeployBundle\Task;

use Sokil\DeployBundle\TaskManager\ProcessRunner;
use Sokil\DeployBundle\TaskManager\ResourceLocator;
use Symfony\Component\Console\Output\OutputInterface;

class GruntTask extends AbstractTask implements
    ResourceAwareTaskInterface,
    ProcessRunnerAwareTaskInterface
{
    /**
     * @var ResourceLocator
     */
    private $resourceLocator;

    /**
     * @var ProcessRunner
     */
    private $processRunner;

    public function setResourceLocator(ResourceLocator $locator)
    {
        $this->resourceLocator = $locator;
        return $this;
    }

    /**
     * @param ProcessRunner $runner
     * @return BowerTask
     */
    public function setProcessRunner(ProcessRunner $runner)
    {
        $this->processRunner = $runner;
        return $this;
    }

    public function run(
        array $commandOptions,
        $environment,
        $verbosity,
        OutputInterface $output
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

            $isSuccessfull = $this->processRunner->run(
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