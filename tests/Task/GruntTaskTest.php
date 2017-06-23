<?php

namespace Sokil\DeployBundle\Task;

use PHPUnit\Framework\TestCase;
use Sokil\DeployBundle\AbstractTestCase;
use Symfony\Component\Console\Output\OutputInterface;

class GruntTaskTest extends AbstractTestCase
{
    public function testRun()
    {
        $taskMock = $this->getMockBuilder(GruntTask::class)
            ->setMethods(['getGruntfilePathList'])
            ->setConstructorArgs([
                'grunt'
            ])
            ->getMock();

        $taskMock
            ->expects($this->any())
            ->method('getGruntfilePathList')
            ->will($this->returnValue([
                'bundle1' => '/tmp/bundle1/Gruntfile.js',
                'bundle2' => '/tmp/bundle2/Gruntfile.js',
            ]));

        $taskMock->setProcessRunner($this->createProcessRunnerMock(
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

        $taskMock->configure([
            'bundles' => [
                'bundle1' => [
                    'tasks' => [
                        'task1',
                        'task2',
                    ],
                ],
                'bundle2' => true,
                'bundle3' => false,
            ]
        ]);

        $taskMock->run(
            [],
            'dev',
            OutputInterface::VERBOSITY_NORMAL,
            $this->createOutput()
        );
    }

    public function testRun_CliConfiguration()
    {
        /* @var $taskMock \Sokil\DeployBundle\Task\GruntTask */
        $taskMock = $this->getMockBuilder(GruntTask::class)
            ->setMethods(['getGruntfilePathList'])
            ->setConstructorArgs([
                'grunt'
            ])
            ->getMock();

        $taskMock
            ->expects($this->any())
            ->method('getGruntfilePathList')
            ->will($this->returnValue([
                'bundle2' => '/tmp/bundle2/Gruntfile.js',
                'bundle3' => '/tmp/bundle3/Gruntfile.js',
            ]));

        $taskMock->setProcessRunner($this->createProcessRunnerMock(
            [
                [
                    'cd /tmp/bundle2; grunt --env=dev task3 task4',
                    'dev',
                    OutputInterface::VERBOSITY_NORMAL,
                    $this->isInstanceOf('Symfony\Component\Console\Output\OutputInterface')
                ],
                [
                    'cd /tmp/bundle3; grunt --env=dev',
                    'dev',
                    OutputInterface::VERBOSITY_NORMAL,
                    $this->isInstanceOf('Symfony\Component\Console\Output\OutputInterface')
                ],
            ],
            true
        ));

        $taskMock->configure([
            'bundles' => [
                'bundle1' => [
                    'tasks' => [
                        'task1',
                        'task2',
                    ],
                ],
                'bundle2' => true,
                'bundle3' => false,
            ]
        ]);

        $taskMock->run(
            ['tasks' => 'bundle2=task3,task4&bundle3'],
            'dev',
            OutputInterface::VERBOSITY_NORMAL,
            $this->createOutput()
        );
    }

    public function parseGruntTaskStringDataProvider()
    {
        return [
            [
                'bundle1',
                [
                    'bundle1' => true,
                ],
            ],
            [
                'bundle1=task1',
                [
                    'bundle1' => ['tasks' => ['task1']],
                ],
            ],
            [
                'bundle1=task1,task2',
                [
                    'bundle1' => ['tasks' => ['task1', 'task2']],
                ],
            ],
            [
                'bundle1=newer:task1,newer:task2',
                [
                    'bundle1' => ['tasks' => ['newer:task1', 'newer:task2']],
                ],
            ],
            [
                'bundle1=task1,task2&bundle2&bundle3=task1',
                [
                    'bundle1' => ['tasks' => ['task1', 'task2']],
                    'bundle2' => true,
                    'bundle3' => ['tasks' => ['task1']]
                ],
            ],
        ];
    }

    /**
     * @dataProvider parseGruntTaskStringDataProvider
     * @param $gruntTaskString
     * @param $expectedTaskConfig
     */
    public function testParseGruntTaskString($gruntTaskString, $expectedTaskConfig)
    {
        $task = new GruntTask('grunt');
        $task->configure(['bundles' => ['bundle42' => true]]);

        // allow call private method
        $taskReflection = new \ReflectionClass($task);
        $methodReflection = $taskReflection->getMethod('parseGruntTaskString');
        $methodReflection->setAccessible(true);

        // call method
        $actualTaskConfig = $methodReflection->invoke($task, $gruntTaskString);
        $this->assertSame($expectedTaskConfig, $actualTaskConfig);
    }
}
