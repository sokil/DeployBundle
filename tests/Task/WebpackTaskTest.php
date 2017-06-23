<?php

namespace Sokil\DeployBundle\Task;

use Sokil\DeployBundle\AbstractTestCase;
use Sokil\DeployBundle\TaskManager;
use Symfony\Component\Console\Output\Output;
use Symfony\Component\Console\Output\OutputInterface;

class WebpackTaskTest extends AbstractTestCase
{
    public function testRun()
    {
        // create task
        $webpackTask = new WebpackTask(
            'webpack'
        );

        $webpackTask->configure([
            'pathToWebpack' => './node_modules/.bin/webpack',
            'projects' => [
                'assets' => [
                    'config' => 'assets/webpack.config.js',
                    'p' => true,
                    'progress' => true,
                ],
            ],
        ]);

        // mock process runner
        $webpackTask->setProcessRunner($this->createProcessRunnerMock(
            [
                [
                    './node_modules/.bin/webpack --config assets/webpack.config.js -p --progress --context assets',
                    'dev',
                    OutputInterface::VERBOSITY_NORMAL,
                    $this->isInstanceOf(Output::class)
                ],
            ],
            true
        ));

        $webpackTask->run(
            [],
            'dev',
            Output::VERBOSITY_NORMAL,
            $this->createOutput()
        );
    }
}
