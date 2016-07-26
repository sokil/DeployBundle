<?php

namespace Sokil\DeployBundle\Task;

use PHPUnit\Framework\TestCase;
use Sokil\DeployBundle\AbstractTestCase;
use Symfony\Component\Console\Output\OutputInterface;

class GruntTaskTest extends AbstractTestCase
{
    public function testRun()
    {
        $taskMock = $this->getMockBuilder('\Sokil\DeployBundle\Task\GruntTask')
            ->setMethods(['getGruntfilePath'])
            ->setConstructorArgs([
                'grunt',
                [
                    'bundles' => [
                        'bundle1' => 'task1 task2',
                        'bundle2' => true,
                    ]
                ]
            ])
            ->getMock();

        $taskMock
            ->expects($this->any())
            ->method('getGruntfilePath')
            ->will($this->returnValueMap([
                ['bundle1', '/tmp/bundle1/Gruntfile.js'],
                ['bundle2', '/tmp/bundle2/Gruntfile.js'],
                ['bundle3', '/tmp/bundle3/Gruntfile.js'],
            ]));

        $taskMock->setProcessRunner($this->createProcessRunner(
            [
                [
                    'cd /tmp/bundle1; grunt --env=dev task1 task2',
                    'dev',
                    OutputInterface::VERBOSITY_NORMAL,
                    $this->isInstanceOf('Symfony\Component\Console\Output\OutputInterface')
                ],
                [
                    'cd /tmp/bundle2; grunt --env=dev',
                    'dev',
                    OutputInterface::VERBOSITY_NORMAL,
                    $this->isInstanceOf('Symfony\Component\Console\Output\OutputInterface')
                ],
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