<?php

/**
 * This file is part of the DeployBundle package.
 *
 * (c) Dmytro Sokil <dmytro.sokil@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sokil\DeployBundle\Task;

use Sokil\DeployBundle\Exception\DeployException;
use Sokil\DeployBundle\Exception\TaskConfigurationValidateException;
use Sokil\DeployBundle\Exception\TaskExecuteException;
use Sokil\DeployBundle\TaskManager\ProcessRunner;
use Sokil\DeployBundle\TaskManager\ResourceLocator;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Run grunt tasks in configured bundles
 *
 * Sample configuration:
 *
 *  grunt:
 *      parallel: true                  # run tasks in parallel or serially
 *      bundles:                        # list of bundles to run grunt tasks
 *          Bundle1Name: true           # allow bundle to run tasks
 *          Bundle2Name: false          # skip bundle (may be omitted)
 *          Bundle3Name: 'less jade'    # list of tasks to run
 *
 * Also tasks may be configured through cli parameter:
 * $ ./app/console deploy --grunt --grunt-tasks="bundle1Name:task1Name,task2Name;bundle2Name;"
 *
 */
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

    /**
     * @var array
     */
    private $bundleTaskList;

    /**
     * @var bool
     */
    private $isParallelRunAllowed = false;

    public function getDescription()
    {
        return 'Run grunt tasks in bundles';
    }

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
     *
     * @throws TaskConfigurationValidateException
     */
    protected function configure(array $options)
    {
        // configure bundle list
        if (empty($options['bundles']) || !is_array($options['bundles'])) {
            throw new TaskConfigurationValidateException(
                'Bundles not specified for grunt task "' . $this->getAlias() . '"'
            );
        }

        $this->bundleTaskList = $options['bundles'];

        // allow fork tasks
        if (!empty($options['parallel'])) {
            $this->isParallelRunAllowed = true;
        }
    }

    protected function getGruntfilePath($bundleName)
    {
        // get bundle path
        $bundlePath = $this->resourceLocator->locateResource('@' . $bundleName);
        // find path to Gruntfile
        $gruntPath = $bundlePath . 'Gruntfile.js';
        if (!file_exists($gruntPath)) {
            throw new TaskConfigurationValidateException(sprintf(
                'Bundle "%s" configured for running grunt task but Gruntfile.js not found at "%s"',
                $bundleName,
                $bundlePath
            ));
        }
        return $gruntPath;
    }

    public function getCommandOptionDefinitions()
    {
        return [
            'tasks' => [
                'description' => 'List of bundles with grunt tasks: "bundle1Name:task1Name,task2Name;bundle2Name;"',
            ]
        ];
    }

    /**
     * Format of task string is: "bundle1Name:task1Name,task2Name;bundle2Name;"...
     *
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
        // get task list
        if (empty($commandOptions['tasks'])) {
            $bundleTasksList = $this->bundleTaskList;
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
            $output->writeln('<' . self::STYLE_H2 . '>Execute grunt tasks from ' . $gruntfilePath . '</>');

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

            if (true === $this->isParallelRunAllowed) {
                $commands[] = $command;
            } else {
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

        // start parallel run
        if (true === $this->isParallelRunAllowed) {
            if (empty($commands)) {
                throw new DeployException('Parallel commands not configured');
            }

            $this->processRunner->parallelRun(
                $commands,
                $environment,
                $verbosity,
                $output
            );
        }
    }
}
