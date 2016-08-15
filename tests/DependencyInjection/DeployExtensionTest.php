<?php

namespace Sokil\DeployBundle\DependencyInjection;

use Sokil\DeployBundle\AbstractTestCase;
use Sokil\DeployBundle\TaskManager;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class DeployExtensionTest extends AbstractTestCase
{
    public function testGetTaskManager()
    {
        $taskManager = $this->getContainer()->get('deploy.task_manager');
        $this->assertInstanceOf('\Sokil\DeployBundle\TaskManager', $taskManager);
    }

    public function testGetSimpleTask()
    {
        $taskManager = $this->getContainer()->get('deploy.task_manager');

        // simple task
        $gitTask = $taskManager->getTask('git');
        $this->assertInstanceOf('\Sokil\DeployBundle\Task\GitTask', $gitTask);

        $this->assertEquals(
            [
                'defaultRemote' => 'origin',
                'defaultBranch' => 'master',
                'repos' => [
                    'core' => [
                        'path' => '/tmp',
                        'branch' => 'master',
                        'remote' => 'origin',
                        'tag' => true
                    ],
                ],
            ],
            $gitTask->getOptions()
        );

    }

    public function testGetResourceAwareTask()
    {
        $taskManager = $this->getContainer()->get('deploy.task_manager');

        // resource aware task
        $gitTask = $taskManager->getTask('grunt');
        $this->assertInstanceOf('\Sokil\DeployBundle\Task\GruntTask', $gitTask);

        $this->assertEquals(
            [
                'bundles' => [
                    'bundle1' => 'gruntTask1 gruntTask2',
                    'bundle2' => true,
                ],
                'parallel' => false,
            ],
            $gitTask->getOptions()
        );
    }
}