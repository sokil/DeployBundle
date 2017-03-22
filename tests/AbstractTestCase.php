<?php

namespace Sokil\DeployBundle;

use PHPUnit\Framework\TestCase;
use Sokil\DeployBundle\Task\AbstractTask;
use Sokil\DeployBundle\TaskManager\CommandLocator;
use Sokil\DeployBundle\TaskManager\ProcessRunner;
use Sokil\DeployBundle\TaskManager\ResourceLocator;
use Symfony\Component\Console\Input\Input;
use Symfony\Component\Console\Output\Output;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Helper\HelperSet;

abstract class AbstractTestCase extends TestCase
{
    /**
     * @var ContainerBuilder
     */
    private $container;

    public function getBundleConfiguration()
    {
        $config = [
            'config' => [
                'git' => [
                    'defaultRemote' => 'origin',
                    'defaultBranch' => 'master',
                    'repos' => [
                        'core' => [
                            'path' => '/tmp',
                            'branch' => 'master',
                            'remote' => 'origin',
                            'tag' => false,
                        ]
                    ],
                ],
                'composer' => [],
                'migrate' => [],
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
                    'bundles' => [
                        'bundle1' => 'gruntTask1 gruntTask2',
                        'bundle2' => true,
                    ]
                ],
                'asseticDump' => [],
                'assetsInstall' => [],
                'clearCache' => [],
                'sync' => [
                    'web' => [
                        'source' => '.',
                        'target' => [
                            'user@web1.server.com://var/www/site',
                            'user@web2.server.com://var/www/site',
                        ],
                        'exclude' => [
                            '/var',
                            '/app/conf/nginx/',
                            '/.idea',
                            '/app/config/parameters.yml',
                        ],
                        'include' => [
                            '/app/conf/nginx/*.conf.sample',
                        ],
                        'deleteExtraneousFiles' => true,
                        'verbose' => true,
                    ],
                    'parallel' => false,
                ]
            ],
        ];

        $config['tasks'] = [
            'default' => array_keys($config['config']),
            'update' => [
                'npm',
                'bower',
            ],
            'compile' => [
                'grunt',
                'asseticDump',
                'assetsInstall',
            ],
            'build' => [
                'update',
                'compile',
            ]
        ];

        return $config;
    }

    /**
     * @return ContainerBuilder
     */
    public function getContainer()
    {
        if ($this->container) {
            return $this->container;
        }

        $this->container = new ContainerBuilder();

        // add compiler pass
        $bundle = new DeployBundle();
        $bundle->build($this->container);

        // load bundle configuration to extension
        $extension = $bundle->getContainerExtension();
        $extension->load([
            0 => $this->getBundleConfiguration(),
        ], $this->container);

        // mock dependencies
        $this->container->set('deploy.task_manager.resource_locator', $this->createResourceLocator());
        $this->container->set('deploy.task_manager.process_runner', $this->createProcessRunner());
        $this->container->set('deploy.task_manager.command_locator', $this->createCommandLocator());

        // create container
        $this->container->compile();

        // set application to console command
        $this->container
            ->get('deploy.console_command')
            ->setApplication($this->createConsoleApplication());

        return $this->container;
    }

    /**
     * @param array $commandMap
     * @return Application
     */
    private function createConsoleApplication(array $commandMap = [])
    {
        // add kernel dependency
        /* @var $application Application */
        $application = $this
            ->getMockBuilder('Symfony\Component\Console\Application')
            ->setMethods(['find', 'getHelperSet'])
            ->disableOriginalConstructor()
            ->getMock();

        $application
            ->expects($this->any())
            ->method('find')
            ->will($this->returnValueMap($commandMap));

        $application
            ->expects($this->any())
            ->method('getHelperSet')
            ->will($this->returnValue(new HelperSet()));

        return $application;
    }

    /**
     * @return ResourceLocator
     */
    private function createResourceLocator()
    {
        // add kernel dependency
        $locator = $this
            ->getMockBuilder('Sokil\DeployBundle\TaskManager\ResourceLocator')
            ->setMethods(['locateResource'])
            ->disableOriginalConstructor()
            ->getMock();

        $locator
            ->expects($this->any())
            ->method('locateResource')
            ->will($this->returnValueMap([
                ['@bundle1', '/path/to/bundle1'],
                ['@bundle2', '/path/to/bundle2'],
                ['@bundle3', '/path/to/bundle3'],
            ]));

        return $locator;
    }

    /**
     * @return CommandLocator
     */
    private function createCommandLocator()
    {
        $locator = new CommandLocator();
        return $locator;
    }

    /**
     * @param array $expectedCommands expected commands
     * @param bool $expectedStatus expected command execution result
     * @return ProcessRunner
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
     * @param string $taskAlias name of task
     * @return AbstractTask
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

        return $task;
    }

    /**
     * @param string $taskAlias name of task
     * @return AbstractTask
     */
    public function createSimpleTaskWithoutAdditionalCommandOptions($taskAlias = 'simpleTask')
    {
        $task = $this->createSimpleTask($taskAlias);

        // add command options
        $task
            ->expects($this->any())
            ->method('getCommandOptionDefinitions')
            ->will($this->returnValue([]));

        return $task;
    }

    /**
     * @return AbstractTask
     */
    public function createSimpleTaskWithAdditionalCommandOptions($taskAlias = 'simpleTask')
    {
        $task = $this->createSimpleTask($taskAlias);

        // add command options
        $task
            ->expects($this->any())
            ->method('getCommandOptionDefinitions')
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
     * @param array $options
     * @return Input
     */
    public function createInput(array $options = [])
    {
        $mock = $this
            ->getMockBuilder(Input::class)
            ->setMethods([
                'getOption',
                'getOptions',
                'parse',
                'getFirstArgument',
                'hasParameterOption',
                'getParameterOption'
            ])
            ->disableOriginalConstructor()
            ->getMock();

        $mock
            ->expects($this->any())
            ->method('getOptions')
            ->will($this->returnValue($options));

        $valueMap = [];
        foreach ($options as $optionName => $optionValue) {
            $valueMap[] = [$optionName, $optionValue];
        }

        $mock
            ->expects($this->any())
            ->method('getOption')
            ->will($this->returnValueMap($valueMap));

        return $mock;
    }

    /**
     * @return Output
     */
    public function createOutput()
    {
        return $this
            ->getMockBuilder(Output::class)
            ->disableOriginalConstructor()
            ->getMock();
    }
}