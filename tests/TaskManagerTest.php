<?php

namespace Sokil\DeployBundle;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\OutputInterface;

class TaskManagerTest extends AbstractTestCase
{
    public function testAddTask()
    {
        $taskManager = new TaskManager(
            [
                'myCustomTask' => [],
            ],
            $this->createProcessRunnerMock(),
            $this->createResourceLocator(),
            $this->createCommandLocator()
        );

        $taskManager
            ->addTask(
                $this->createSimpleTaskWithAdditionalCommandOptions('myCustomTask')
            );

        $this->assertEquals(
            'myCustomTask',
            $taskManager->getTask('myCustomTask')->getAlias()
        );
    }

    /**
     * @expectedException \Sokil\DeployBundle\Exception\TaskNotFoundException
     * @expectedExceptionMessage Task with alias "someUnexistedTask" not found
     */
    public function testGetNotExistedTask()
    {
        $taskManager = $this->getContainer()->get('deploy.task_manager');
        $taskManager->getTask('someUnexistedTask');
    }

    public function testGetTasks()
    {
        $tasksConfiguration = [
            'myCustomTask1' => [],
            'myCustomTask2' => [],
        ];

        $taskManager = new TaskManager(
            $tasksConfiguration,
            $this->createProcessRunnerMock(),
            $this->createResourceLocator(),
            $this->createCommandLocator()
        );

        $taskManager->addTask($this->createSimpleTaskWithAdditionalCommandOptions('myCustomTask1'));
        $taskManager->addTask($this->createSimpleTaskWithAdditionalCommandOptions('myCustomTask2'));

        $registeredTasks = $taskManager->getTasks();

        // test list
        $this->assertSame(
            array_keys($tasksConfiguration),
            array_keys($registeredTasks)
        );

        // test task
        $this->assertEquals(
            'myCustomTask2',
            $registeredTasks['myCustomTask2']->getAlias()
        );
    }

    public function testConfigureCommand_NoTasks()
    {
        $taskManager = $this->getContainer()->get('deploy.task_manager');
        $taskManager->configureCommand(
            $this->getContainer()->get('deploy.console_command')
        );
    }

    public function testConfigureCommand_TaskWithoutAdditionalCommandOptions()
    {
        // create manager an add task
        $taskManager = new TaskManager(
            [
                'myCustomTask' => [],
            ],
            $this->createProcessRunnerMock(),
            $this->createResourceLocator(),
            $this->createCommandLocator()
        );

        $taskManager->addTask(
            $this->createSimpleTaskWithoutAdditionalCommandOptions('myCustomTask')
        );

        // configure command
        $command = $this->getContainer()->get('deploy.console_command');
        $taskManager->configureCommand($command);

        $this->assertEquals(
            'myCustomTask',
            $command->getDefinition()->getOption('myCustomTask')->getName()
        );
    }

    public function testConfigureCommand_TaskWithAdditionalCommandOptions()
    {
        // create manager
        $taskManager = new TaskManager(
            [
                'myCustomTask' => [],
            ],
            $this->createProcessRunnerMock(),
            $this->createResourceLocator(),
            $this->createCommandLocator()
        );

        // add task
        $myCustomTask = $this->createSimpleTaskWithAdditionalCommandOptions('myCustomTask');
        $taskManager->addTask($myCustomTask);

        // create command
        $command = $this->getContainer()->get('deploy.console_command');
        $taskManager->configureCommand($command);

        $this->assertEquals(
            'myCustomTask',
            $command->getDefinition()->getOption('myCustomTask')->getName()
        );

        $this->assertEquals(
            'myCustomTask-optionName1',
            $command->getDefinition()->getOption('myCustomTask-optionName1')->getName()
        );

        $this->assertEquals(
            'myCustomTask-optionName2',
            $command->getDefinition()->getOption('myCustomTask-optionName2')->getName()
        );
    }

    public function testExecute()
    {
        // create task manager
        $taskManager = new TaskManager(
            [
                'task1' => [],
                'task2' => [],
            ],
            $this->createProcessRunnerMock(),
            $this->createResourceLocator(),
            $this->createCommandLocator()
        );

        // add tasks
        $task1 = $this->createSimpleTaskWithoutAdditionalCommandOptions('task1');
        $task1
            ->expects($this->once())
            ->method('run')
            ->with(
                $this->isType('array'),
                $this->equalTo('dev'),
                $this->equalTo(OutputInterface::VERBOSITY_NORMAL),
                $this->createOutput()
            );
        $taskManager->addTask($task1);

        $task2 = $this->createSimpleTaskWithoutAdditionalCommandOptions('task2');
        $task2
            ->expects($this->once())
            ->method('run')
            ->with(
                $this->isType('array'),
                $this->equalTo('dev'),
                $this->equalTo(OutputInterface::VERBOSITY_NORMAL),
                $this->createOutput()
            );
        $taskManager->addTask($task2);

        // create command
        $command = $this->getContainer()->get('deploy.console_command');
        $taskManager->configureCommand($command);

        // mock input
        $input = $this->createInput([
            'task1' => true,
            'task2' => true,
            'env' => 'dev',
        ]);

        // mock output
        $output = $this->createOutput();
        $output
            ->expects($this->any())
            ->method('getVerbosity')
            ->will($this->returnValue(OutputInterface::VERBOSITY_NORMAL));

        // execute tasks
        $taskManager->execute(
            $input,
            $output
        );
    }

    public function testInputMock()
    {
        $input = $this->createInput([
            'env' => 'dev',
        ]);

        $this->assertEquals(['env' => 'dev'], $input->getOptions());

        $this->assertEquals('dev', $input->getOption('env'));
    }

    public function testGetTasksFromCliOptions_OnlyGit()
    {
        // get task manager
        $taskManager = $this->getContainer()->get('deploy.task_manager');

        // mock input
        $input = $this
            ->getMockBuilder('Symfony\Component\Console\Input\Input')
            ->disableOriginalConstructor()
            ->setMethods(['getOptions', 'parse', 'getFirstArgument', 'hasParameterOption', 'getParameterOption'])
            ->getMock();

        $input
            ->expects($this->once())
            ->method('getOptions')
            ->will($this->returnValue([
                'git' => true,
                'bower' => false,
                'npm' => false,
            ]));

        // get method
        $taskManagerReflection = new \ReflectionClass($taskManager);
        $getTaskAliasesFromCliOptionsMethod = $taskManagerReflection->getMethod('getTasksFromCliOptions');
        $getTaskAliasesFromCliOptionsMethod->setAccessible(true);

        $tasks = $getTaskAliasesFromCliOptionsMethod->invoke(
            $taskManager,
            $input
        );

        $this->assertEquals(['git'], array_keys($tasks));
    }

    public function testGetTasksFromCliOptions_NoTasksSpecified_UseDefaultBundle()
    {
        // get task manager
        $taskManager = $this->getContainer()->get('deploy.task_manager');

        // mock input
        $input = $this->createInput();

        // get method
        $taskManagerReflection = new \ReflectionClass($taskManager);
        $isAllTasksRequiredMethod = $taskManagerReflection->getMethod('getTasksFromCliOptions');
        $isAllTasksRequiredMethod->setAccessible(true);

        $tasks = $isAllTasksRequiredMethod->invoke($taskManager, $input);

        $this->assertEquals([
            'git',
            'composer',
            'migrate',
            'npm',
            'bower',
            'grunt',
            'asseticDump',
            'assetsInstall',
            'clearCache',
            'sync'
        ], array_keys($tasks));
    }

    public function testGetTasksFromCliOptions_NoTasksSpecified_CustomBundleWithTasksSpecified()
    {
        // get task manager
        $taskManager = $this->getContainer()->get('deploy.task_manager');

        // mock input
        $input = $this->createInput([
            'compile' => true,
        ]);

        // get method
        $taskManagerReflection = new \ReflectionClass($taskManager);
        $isAllTasksRequiredMethod = $taskManagerReflection->getMethod('getTasksFromCliOptions');
        $isAllTasksRequiredMethod->setAccessible(true);

        $tasks = $isAllTasksRequiredMethod->invoke($taskManager, $input);

        $this->assertEquals([
            'grunt',
            'asseticDump',
            'assetsInstall',
        ], array_keys($tasks));
    }

    public function testGetTasksFromCliOptions_NoTasksSpecified_CustomBundleWithBundlesSpecified()
    {
        // get task manager
        $taskManager = $this->getContainer()->get('deploy.task_manager');

        // mock input
        $input = $this->createInput([
            'build' => true,
        ]);

        // get method
        $taskManagerReflection = new \ReflectionClass($taskManager);
        $isAllTasksRequiredMethod = $taskManagerReflection->getMethod('getTasksFromCliOptions');
        $isAllTasksRequiredMethod->setAccessible(true);

        $tasks = $isAllTasksRequiredMethod->invoke($taskManager, $input);

        $this->assertEquals([
            'npm',
            'bower',
            'grunt',
            'asseticDump',
            'assetsInstall',
        ], array_keys($tasks));
    }

    public function testGetTasksFromCliOptions_TaskSpecified_CustomBundleWithBundlesSpecified()
    {
        // get task manager
        $taskManager = $this->getContainer()->get('deploy.task_manager');

        // mock input
        $input = $this->createInput([
            'git' => true,
            'build' => true,
        ]);

        // get method
        $taskManagerReflection = new \ReflectionClass($taskManager);
        $isAllTasksRequiredMethod = $taskManagerReflection->getMethod('getTasksFromCliOptions');
        $isAllTasksRequiredMethod->setAccessible(true);

        $tasks = $isAllTasksRequiredMethod->invoke($taskManager, $input);

        $this->assertEquals([
            'git',
            'npm',
            'bower',
            'grunt',
            'asseticDump',
            'assetsInstall',
        ], array_keys($tasks));
    }
}
