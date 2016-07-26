<?php

namespace Sokil\DeployBundle\Task;

use PHPUnit\Framework\TestCase;
use Sokil\DeployBundle\AbstractTestCase;
use Symfony\Component\Console\Output\OutputInterface;

class GitTaskTest extends AbstractTestCase
{
    public function testRun()
    {
        $taskMock = $this
            ->getMockBuilder('Sokil\DeployBundle\Task\GitTask')
            ->setMethods(['buildReleaseTag'])
            ->setConstructorArgs([
                'git',
                [
                    'defaultRemote' => 'origin',
                    'defaultBranch' => 'master',
                    'repos' => [
                        'core' => [
                            'path' => sys_get_temp_dir(),
                            'branch' => 'master',
                            'remote' => 'origin',
                            'tag' => true
                        ]
                    ],
                ]
            ])
            ->getMock();

        $taskMock
            ->expects($this->any())
            ->method('buildReleaseTag')
            ->will($this->returnValue('tagPattern'));

        $taskMock->setProcessRunner($this->createProcessRunner(
            [
                [
                    'cd /tmp; git pull origin master',
                    'dev',
                    OutputInterface::VERBOSITY_NORMAL,
                    $this->isInstanceOf('Symfony\Component\Console\Output\OutputInterface')
                ],
                [
                    'git tag -a tagPattern -m tagPattern',
                    'dev',
                    OutputInterface::VERBOSITY_NORMAL,
                    $this->isInstanceOf('Symfony\Component\Console\Output\OutputInterface')
                ]
            ],
            true
        ));

        $taskMock->run(
            [],
            'dev',
            OutputInterface::VERBOSITY_NORMAL,
            $this->createOutput()
        );
    }
}