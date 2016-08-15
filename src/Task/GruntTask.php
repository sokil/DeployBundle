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
    }

    /**
     * @param ProcessRunner $runner
     * @return BowerTask
     */
    public function setProcessRunner(ProcessRunner $runner)
    {
        $this->processRunner = $runner;
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

    public function getCommandOptions()
    {
        return [
            'tasks' => [
                'description' => 'list of bundles with specified grunt tasks',
            ]
        ];
    }

    /**
     * Format of task string is: "bundle1Name:task1Name,task2Name;bundle2Name;"...
     * @param $taskString
     * @return array
     */
    protected function parseGruntTaskString($taskString)
    {
        $gruntTaskConfig = [];
        foreach (explode('&', $taskString) as $bundleTaskString) {
            if (false === strpos($bundleTaskString, '=')) {
                $gruntTaskConfig[$bundleTaskString] = true;
            } else {
                list ($bundleTame, $commaDelimitedGruntTasks) = explode('=', $bundleTaskString);
                $gruntTaskConfig[$bundleTame] = ['tasks' => explode(',', $commaDelimitedGruntTasks)];
            }
        }

        return $gruntTaskConfig;
    }

    public function run(
        array $commandOptions,
        $environment,
        $verbosity,
        OutputInterface $output
    ) {
        if (empty($commandOptions['tasks'])) {
            $bundleTasksList = $this->getOption('bundles');
        } else {
            $bundleTasksList = $this->parseGruntTaskString($commandOptions['tasks']);
        }

        // get path list to Gruntfile
        $gruntfilePathList = [];
        foreach ($bundleTasksList as $bundleName => $bundleTaskConfiguration) {
            // store bundle path
            $gruntfilePathList[$bundleName] = $this->getGruntfilePath($bundleName);
        }

        // run task
        foreach ($gruntfilePathList as $bundleName => $gruntfilePath) {

            $output->writeln('<' . $this->h2Style . '>Execute grunt tasks from ' . $gruntfilePath . '</>');

            // configure grunt tasks
            $bundleTaskConfiguration = $bundleTasksList[$bundleName];
            $bundleGruntTasks = null;
            if (is_array($bundleTaskConfiguration)) {
                if (!empty($bundleTaskConfiguration['tasks']) && is_array($bundleTaskConfiguration['tasks'])) {
                    $bundleGruntTasks = ' ' . implode(' ', $bundleTaskConfiguration['tasks']);
                }
            } elseif (is_bool($bundleTaskConfiguration)) {
                if ($bundleTaskConfiguration === false) {
                    continue;
                }
            }

            // prepare command
            $commandPattern = 'cd %s; grunt --env=%s%s';
            $command = sprintf(
                $commandPattern,
                dirname($gruntfilePath),
                $environment,
                $bundleGruntTasks
            );

            $isSuccessful = $this->processRunner->run(
                $command,
                $environment,
                $verbosity,
                $output
            );

            if (!$isSuccessful) {
                throw new TaskExecuteException('Error running grunt tasks for bundle ' . $bundleName);
            }

            $output->writeln('Grunt tasks for bundle ' . $bundleName . ' executed successfully');
        }
    }
}