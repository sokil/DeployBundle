<?php

namespace Sokil\DeployBundle;

use Sokil\DeployBundle\TaskManager\AbstractTask;
use Sokil\DeployBundle\TaskManager\CommandLocator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;

abstract class AbstractTestCase extends TestCase
{
    /**
     * @param array $commandMap
     * @return \PHPUnit_Framework_MockObject_MockObject|Application
     */
    public function createConsoleApplication(array $commandMap = [])
    {
        // add kernel dependency
        $application = $this
            ->getMockBuilder('Symfony\Component\Console\Application')
            ->setMethods(['find'])
            ->disableOriginalConstructor()
            ->getMock();

        $application
            ->expects($this->any())
            ->method('find')
            ->will($this->returnValueMap($commandMap));

        return $application;
    }

    /**
     * @return ResourceLocator|\PHPUnit_Framework_MockObject_MockObject
     */
    public function createResourceLocator()
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

    /**
     * @return CommandLocator
     */
    public function createCommandLocator()
    {
        $locator = new CommandLocator();
        return $locator;
    }

    /**
     * @return ResourceLocator|\PHPUnit_Framework_MockObject_MockObject
     */
    public function createProcessRunner(
        array $expectedCommands = [],
        $expectedStatus = true
    ) {
        // add kernel dependency
        $locator = $this
            ->getMockBuilder('Sokil\DeployBundle\TaskManager\ProcessRunner')
            ->disableOriginalConstructor()
            ->getMock();

        $invocationMocker = $locator
            ->expects($this->exactly(count($expectedCommands)))
            ->method('run')
            ->will($this->returnValue($expectedStatus));

        call_user_func_array([$invocationMocker, 'withConsecutive'], $expectedCommands);

        return $locator;
    }

    /**
     * @return ContainerBuilder
     */
    public function createContainer()
    {
        $container = new ContainerBuilder();

        // add compiler pass
        $bundle = new DeployBundle();
        $bundle->build($container);

        // load bundle configuration to extension
        $extension = $bundle->getContainerExtension();
        $extension->load([
            0 => [
                'tasks' => [
                    'git' => [
                        'defaultRemote' => 'origin',
                        'defaultBranch' => 'master',
                        'repos' => [
                            'core' => [
                                'path' => '/var/www/core',
                                'branch' => 'master',
                                'remote' => 'origin',
                                'tag' => true
                            ]
                        ],
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
                            'bundle2' => true,
                        ]
                    ],
                    'asseticDump' => [],
                    'assetsInstall' => [],
                    'clearCache' => [],
                ]
            ]
        ], $container);

        // mock dependencies
        $container->set('deploy.task_manager.resource_locator', $this->createResourceLocator());

        // create container
        $container->compile();

        $container->get('deploy.task_manager.resource_locator');

        return $container;
    }

    /**
     * @param string $taskAlias name of task
     * @return AbstractTask|\PHPUnit_Framework_MockObject_MockObject
     */
    private function createSimpleTask($taskAlias = 'simpleTask')
    {
        $task = $this
            ->getMockBuilder(
                'Sokil\DeployBundle\Task\AbstractTask'
            )
            ->disableOriginalConstructor()
            ->getMock();

        // alias
        $task
            ->expects($this->any())
            ->method('getAlias')
            ->will($this->returnValue($taskAlias));

        // task options
        $task
            ->expects($this->any())
            ->method('getOptions')
            ->will($this->returnValue([
                'option1' => 'value1',
                'option2' => 'value2',
            ]));

        return $task;
    }

    /**
     * @param string $taskAlias name of task
     * @return AbstractTask|\PHPUnit_Framework_MockObject_MockObject
     */
    public function createSimpleTaskWithoutAdditionalCommandOptions($taskAlias = 'simpleTask')
    {
        $task = $this->createSimpleTask($taskAlias);

        // add command options
        $task
            ->expects($this->any())
            ->method('getCommandOptions')
            ->will($this->returnValue([]));

        return $task;
    }

    /**
     * @return AbstractTask|\PHPUnit_Framework_MockObject_MockObject
     */
    public function createSimpleTaskWithAdditionalCommandOptions($taskAlias = 'simpleTask')
    {
        $task = $this->createSimpleTask($taskAlias);

        // add command options
        $task
            ->expects($this->any())
            ->method('getCommandOptions')
            ->will($this->returnValue([
                'optionName1' => [
                    'description' => 'Description of optionName1',
                ],
                'optionName2' => [
                    'description' => 'Description of optionName2',
                ],
            ]));

        return $task;
    }

    /**
     * @return InputInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    public function createInput()
    {
        return $this
            ->getMockBuilder('Symfony\Component\Console\Input\InputInterface')
            ->getMock();
    }

    /**
     * @return OutputInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    public function createOutput()
    {
        return $this
            ->getMockBuilder('Symfony\Component\Console\Output\OutputInterface')
            ->getMock();
    }
}