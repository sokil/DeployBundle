<?php

namespace Sokil\DeployBundle;

use Sokil\DeployBundle\DependencyInjection\DeployExtension;
use Sokil\DeployBundle\TaskManager\ResourceLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class DeployExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ContainerBuilder
     */
    private $container;

    public function setUp()
    {
        $this->container = new ContainerBuilder();


        // add compiler pass
        $bundle = new DeployBundle();
        $bundle->build($this->container);

        // load bundle configuration to extension
        $extension = $bundle->getContainerExtension();
        $extension->load([
            0 => [
                'tasks' => [
                    'git' => [
                        'core' => [
                            'path' => '/var/www/core',
                            'branch' => 'master',
                            'remote' => 'origin',
                        ]
                    ],
                    'composer' => [],
                    'migration' => [],
                    'npm' => [
                        'bundles' => [
                            "bundle1",
                            "bundle2",
                            "bundle3",
                        ],
                    ],
                    'bower' => [
                        'bundles' => [
                            "bundle1",
                            "bundle2",
                            "bundle3",
                        ],
                    ],
                    'grunt' => [
                        'tasks' => [
                            'bundle1' => 'gruntTask1 gruntTask2',
                            'bundle2' => 'gruntTask1',
                        ]
                    ],
                    'asseticDump' => [],
                    'assetsInstall' => [],
                    'clearCache' => [],
                ]
            ]
        ], $this->container);

        // mock dependencies
        $this->container->set('deploy.task_manager.resource_locator', $this->createResourceLocatorMock());

        // create container
        $this->container->compile();

        $this->container->get('deploy.task_manager.resource_locator');
    }

    /**
     * @return ResourceLocator
     */
    private function createResourceLocatorMock()
    {
        // add kernel dependency
        $locator = $this
            ->getMockBuilder('Sokil\DeployBundle\TaskManager\ResourceLocator')
            ->disableOriginalConstructor()
            ->getMock();

        $locator
            ->expects($this->any())
            ->method('locateResource')
            ->will($this->returnValueMap([
                ['bundle1', '/path/to/bundle1'],
                ['bundle2', '/path/to/bundle2'],
                ['bundle3', '/path/to/bundle3'],
            ]));

        return $locator;
    }

    public function testGetTaskManager()
    {
        $taskManager = $this->container->get('deploy.task_manager');
        $this->assertInstanceOf('\Sokil\DeployBundle\Taskmanager', $taskManager);

        $gitTask = $taskManager->getTask('git');
        $this->assertInstanceOf('\Sokil\DeployBundle\Task\GitTask', $gitTask);

        $this->assertEquals(
            [
                'core' => [
                    'path' => '/var/www/core',
                    'branch' => 'master',
                    'remote' => 'origin',
                ]
            ],
            $gitTask->getOptions()
        );
    }
}