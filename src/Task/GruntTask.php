<?php

namespace Sokil\DeployBundle\Task;

use Sokil\DeployBundle\Exception\TaskConfigurationValidateException;
use Sokil\DeployBundle\Exception\TaskExecuteException;
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

    /**
     * @param array $options
     * @return array
     * @throws TaskConfigurationValidateException
     */
    public function prepareOptions(array $options)
    {
        // check if bundles passed
        if (empty($options['bundles']) || !is_array($options['bundles'])) {
            throw new TaskConfigurationValidateException('Bundles not specified for grunt task "' . $this->getAlias() . '"');
        }

        return $options;
    }

    protected function getGruntfilePath($bundleName)
    {
        // get bundle path
        $bundlePath = $this->resourceLocator->locateResource('@' . $bundleName);
        // find path to Gruntfile
        $gruntPath = $bundlePath . 'Gruntfile.js';
        if (!file_exists($gruntPath)) {
            throw new TaskConfigurationValidateException('Bundle "' . $bundleName . '" configured for running grunt task but Gruntfile.js not found at "' . $bundlePath . '"');
        }
        return $gruntPath;
    }

    public function run(
        array $commandOptions,
        $environment,
        $verbosity,
        OutputInterface $output
    ) {
        $bundleTasksList = $this->getOption('bundles');

        // get path list to Gruntfile
        $gruntfilePathList = [];
        foreach ($bundleTasksList as $bundleName => $tasks) {
            // store bundle path
            $gruntfilePathList[$bundleName] = $this->getGruntfilePath($bundleName);
        }

        // run task
        foreach ($gruntfilePathList as $bundleName => $gruntfilePath) {

            $output->writeln('<' . $this->h2Style . '>Execute grunt tasks from ' . $gruntfilePath . '</>');

            // prepare command
            $command = 'cd ' . dirname($gruntfilePath) . '; grunt --env=' . $environment;

            // configure grunt tasks
            if (is_string($bundleTasksList[$bundleName])) {
                $command .= ' ' . $bundleTasksList[$bundleName];
            }

            $isSuccessful = $this->processRunner->run(
                $command,
                $environment,
                $verbosity,
                function($output) {
                    $output->writeln('Grunt tasks executed successfully');
                },
                function($output) {
                    $output->writeln('<error>Error executing grunt tasks</error>');
                },
                $output
            );

            if (!$isSuccessful) {
                throw new TaskExecuteException('Error updating bower dependencies for bundle ' . $bundleName);
            }
        }
    }
}