<?php

namespace Sokil\DeployBundle;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;

class TaskManagerTest extends AbstractTestCase
{
    public function testAddTask()
    {
        $taskManager = $this->getContainer()->get('deploy.task_manager');

        $taskManager->addTask($this->createSimpleTaskWithAdditionalCommandOptions('myCustomTask'));


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
        $taskManager = $this->getContainer()->get('deploy.task_manager');
        $taskManager->addTask($this->createSimpleTaskWithAdditionalCommandOptions('myCustomTask'));
        $tasks = $taskManager->getTasks();

        $registeredTasks = $this->getBundleConfiguration()['config'];
        $registeredTasks['myCustomTask'] = [];

        // test list
        $this->assertSame(
            array_keys($registeredTasks),
            array_keys($tasks)
        );

        // test task
        $this->assertEquals(
            'myCustomTask',
            $tasks['myCustomTask']->getAlias()
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
        $taskManager = $this->getContainer()->get('deploy.task_manager');
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
        $taskManager = $this->getContainer()->get('deploy.task_manager');

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
        $taskManager = $this->getContainer()->get('deploy.task_manager');

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
        $input = $this->createInput();
        $input
            ->expects($this->any())
            ->method('getOptions')
            ->will($this->returnValue([
                'task1' => true,
                'task2' => true,
                'env' => 'dev',
            ]));

        $input
            ->expects($this->any())
            ->method('getOption')
            ->will($this->returnValueMap([
                ['task1', true],
                ['task2', true],
                ['env', 'dev'],
            ]));

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

    public function testIsAllTasksRequired_OnlyGit()
    {
        // get task manager
        $taskManager = $this->getContainer()->get('deploy.task_manager');

        // get method
        $taskManagerReflection = new \ReflectionClass($taskManager);
        $isAllTasksRequiredMethod = $taskManagerReflection->getMethod('isAllTasksRequired');
        $isAllTasksRequiredMethod->setAccessible(true);

        $isAllTasksRequired = $isAllTasksRequiredMethod->invoke($taskManager, [
            'git' => true,
            'bower' => false,
            'npm' => false,
        ]);

        $this->assertFalse($isAllTasksRequired);
    }

    public function testIsAllTasksRequired_AllRequired()
    {
        // get task manager
        $taskManager = $this->getContainer()->get('deploy.task_manager');

        // get method
        $taskManagerReflection = new \ReflectionClass($taskManager);
        $isAllTasksRequiredMethod = $taskManagerReflection->getMethod('isAllTasksRequired');
        $isAllTasksRequiredMethod->setAccessible(true);

        $isAllTasksRequired = $isAllTasksRequiredMethod->invoke($taskManager, [
            'git' => false,
            'bower' => false,
            'npm' => false,
        ]);

        $this->assertTrue($isAllTasksRequired);
    }
}