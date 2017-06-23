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

use Sokil\DeployBundle\Exception\TaskConfigurationValidateException;
use Sokil\DeployBundle\TaskManager\ProcessRunner;
use Symfony\Component\Console\Output\OutputInterface;

class WebpackTask extends AbstractTask implements ProcessRunnerAwareTaskInterface
{
    /**
     * @var ProcessRunner
     */
    private $processRunner;

    /**
     * @var array[]
     */
    private $projects = [];

    /**
     * @var string
     */
    private $pathToWebpack;

    /**
     * Get console command description
     * @return mixed
     */
    public function getDescription()
    {
        return 'Sync files between servers';
    }

    /**
     * Prepare task options configured in bundle`s config: check values and set default values
     *
     * @param array $options configuration
     *
     * @throws TaskConfigurationValidateException
     */
    protected function configure(array $options)
    {
        // set path to webpack
        $this->pathToWebpack = !empty($options['pathToWebpack'])
            ? realpath($options['pathToWebpack'])
            : 'webpack';

        if (empty($this->pathToWebpack)) {
            throw new TaskConfigurationValidateException('Path to webpack is invalid');
        }

        // set working dirs
        if (empty($options['projects']) || !is_array($options['projects'])) {
            throw new TaskConfigurationValidateException('Empty webpack "projects" configuration parameter');
        }

        foreach ($options['projects'] as $projectId => $project) {
            // project dir
            if (empty($project['dir'])) {
                throw new TaskConfigurationValidateException(sprintf('Project %s has no "dir" parameter', $projectId));
            }

            // project options. all options will be passed to webpack as arguments
            if (!empty($project['options'])) {
                if (!is_array($project['options'])) {
                    throw new TaskConfigurationValidateException(sprintf('Project %s has invalid "options" parameter', $projectId));
                }
            } else {
                $project['options'] = [];
            }

            // register project
            $this->addProject($project['dir'], $project['options']);
        }
    }

    /**
     * @param ProcessRunner $runner
     */
    public function setProcessRunner(ProcessRunner $runner)
    {
        $this->processRunner = $runner;
    }

    /**
     * Add project configuration
     *
     * @param string $dir
     * @param array $options
     *
     * @throws TaskConfigurationValidateException
     */
    private function addProject($dir, array $options = [])
    {
        if (!file_exists($dir)) {
            throw new TaskConfigurationValidateException(sprintf('Project directory "%s" not found', $dir));
        }

        $this->projects[] = [
            'dir' => realpath($dir),
            'options' => $options
        ];
    }

    /**
     * Run task
     *
     * @param array $commandOptions
     * @param $environment
     * @param $verbosity
     * @param OutputInterface $output
     *
     * @throws TaskConfigurationValidateException
     */
    public function run(
        array $commandOptions,
        $environment,
        $verbosity,
        OutputInterface $output
    ) {
        foreach ($this->projects as $project) {
            $command = [
                'cd ' . $project['dir'] . '; ',
                $this->pathToWebpack
            ];

            // force production flag in production env
            if ($environment === 'prod' && empty($project['options']['p'])) {
                $project['options']['p'] = true;
            }

            // build command
            foreach ($project['options'] as $webpackOptionName => $webpackOptionValue) {
                if (is_bool($webpackOptionValue)) {
                    // flag
                    if ($webpackOptionValue === true) {
                        if (strlen($webpackOptionName) === 1) {
                            $command[] = '-' . $webpackOptionName;
                        } else {
                            $command[] = '--' . $webpackOptionName;
                        }
                    }
                } elseif (is_array($webpackOptionValue)) {
                    // array argument
                    foreach ($webpackOptionValue as $commandOptionValueElement) {
                        $command[] = '--' . $webpackOptionName . ' ' . $commandOptionValueElement;
                    }
                } else {
                    // scalar argument
                    $command[] = '--' . $webpackOptionName . ' ' . $webpackOptionValue;
                }
            }

            // build command list
            $commandString = implode(' ', $command);

            $this->processRunner->run(
                $commandString,
                $environment,
                $verbosity,
                $output
            );
        }
    }
}
