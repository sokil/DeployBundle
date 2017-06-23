<?php

namespace Sokil\DeployBundle\Task;

use Sokil\DeployBundle\AbstractTestCase;
use Sokil\DeployBundle\Event\AfterTasksEvent;
use Symfony\Component\Console\Output\OutputInterface;
use Sokil\DeployBundle\Task\GitTask;

class GitTaskTest extends AbstractTestCase
{
    public function testRun_NoTagRelease()
    {
        $task = new GitTask('git');
        $task->configure([
            'defaultRemote' => 'origin',
            'defaultBranch' => 'master',
            'repos' => [
                'core' => [
                    'path' => sys_get_temp_dir(),
                    'branch' => 'master',
                    'remote' => 'origin',
                    'tag' => false
                ]
            ],
        ]);

        $task->setProcessRunner($this->createProcessRunnerMock(
            [
                [
                    'cd /tmp; git pull origin master',
                    'dev',
                    OutputInterface::VERBOSITY_NORMAL,
                    $this->isInstanceOf('Symfony\Component\Console\Output\OutputInterface')
                ],
            ],
            true
        ));

        $task->run(
            [],
            'dev',
            OutputInterface::VERBOSITY_NORMAL,
            $this->createOutput()
        );
    }

    public function testRun_TagRelease()
    {
        $task = new  GitTask(
            'git'
        );

        $task->configure([
            'defaultRemote' => 'origin',
            'defaultBranch' => 'master',
            'repos' => [
                'core' => [
                    'path' => sys_get_temp_dir(),
                    'branch' => 'master',
                    'remote' => 'origin',
                    'tag' => 'someTagPattern'
                ]
            ],
        ]);

        $task->setProcessRunner($this->createProcessRunnerMock(
            [
                [
                    'cd /tmp; git pull origin master',
                    'dev',
                    OutputInterface::VERBOSITY_NORMAL,
                    $this->isInstanceOf('Symfony\Component\Console\Output\OutputInterface')
                ],
                [
                    'git tag -a someTagPattern -m someTagPattern',
                    'dev',
                    OutputInterface::VERBOSITY_NORMAL,
                    $this->isInstanceOf('Symfony\Component\Console\Output\OutputInterface')
                ]
            ],
            true
        ));

        $output = $this->createOutput();

        $task->run(
            [],
            'dev',
            OutputInterface::VERBOSITY_NORMAL,
            $output
        );

        // trigger post run event
        $task->onAfterTasksFinishedTagRelease(new AfterTasksEvent(
            'dev',
            OutputInterface::VERBOSITY_NORMAL,
            $output
        ));

    }
}
