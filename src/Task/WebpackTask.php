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
     * @var array
     */
    private $options;

    /**
     * @var ProcessRunner
     */
    private $processRunner;

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
        $this->options = $options;
    }

    /**
     * @param ProcessRunner $runner
     */
    public function setProcessRunner(ProcessRunner $runner)
    {
        $this->processRunner = $runner;
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
        $webpackOptions = $this->options;

        $command = [];
        
        // get working dir
        $workingDir = !empty($webpackOptions['workingDir'])
            ? $webpackOptions['workingDir']
            : null;
        
        if ($workingDir) {
            $command[] = 'cd ' . $workingDir . ';';
        }
        
        unset($webpackOptions['workingDir']);
        
        // get path to webpack
        $webpackPath = !empty($webpackOptions['webpackPath'])
            ? $webpackOptions['webpackPath']
            : 'webpack';

        unset($webpackOptions['webpackPath']);
        
        $command[] = $webpackPath;
        
        // force production flag in production env
        if ($environment === 'prod' && empty($webpackOptions['p'])) {
            $webpackOptions['p'] = true;
        }

        // build command
        foreach ($webpackOptions as $webpackOptionName => $webpackOptionValue) {
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
