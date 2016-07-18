<?php

namespace Sokil\DeployBundle\DependencyInjection;

use Sokil\DeployBundle\AbstractTestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class DeployExtensionTest extends AbstractTestCase
{
    /**
     * @var ContainerBuilder
     */
    private $container;

    public function setUp()
    {
        $this->container = $this->createContainer();
    }

    public function testGetTaskManager()
    {
        $taskManager = $this->container->get('deploy.task_manager');
        $this->assertInstanceOf('\Sokil\DeployBundle\TaskManager', $taskManager);

        // simple task
        $gitTask = $taskManager->getTask('git');
        $this->assertInstanceOf('\Sokil\DeployBundle\Task\GitTask', $gitTask);

        $this->assertEquals(
            [
                'defaultRemote' => 'origin',
                'defaultBranch' => 'master',
                'repos' => [
                    'core' => [
                        'path' => '/var/www/core',
                        'branch' => 'master',
                        'remote' => 'origin',
                        'tag' => true
                    ],
                ],
            ],
            $gitTask->getOptions()
        );

        // resource aware task
        $gitTask = $taskManager->getTask('grunt');
        $this->assertInstanceOf('\Sokil\DeployBundle\Task\GruntTask', $gitTask);

        $this->assertEquals(
            [
                'tasks' => [
                    'bundle1' => 'gruntTask1 gruntTask2',
                    'bundle2' => true,
                ]
            ],
            $gitTask->getOptions()
        );
    }
}