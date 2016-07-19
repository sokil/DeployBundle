<?php

namespace Sokil\DeployBundle;

use Symfony\Component\Console\Command\Command;

class TaskManagerTest extends AbstractTestCase
{
    public function testAddTask()
    {
        $taskManager = new TaskManager();

        $taskManager->addTask($this->createSimpleTaskWithAdditionalCommandOptions('myCustomTask'));

        $command = new Command('SomeCommand');
        $taskManager->configureCommand($command);

        $this->assertEquals(
            'myCustomTask',
            $taskManager->getTask('myCustomTask')->getAlias()
        );
    }

    public function testConfigureCommand_NoTasks()
    {
        $taskManager = new TaskManager();

        $command = new Command('SomeCommand');
        $taskManager->configureCommand($command);
    }

    public function testConfigureCommand_TaskWithoutAdditionalCommandOptions()
    {
        $taskManager = new TaskManager();

        $taskManager->addTask($this->createSimpleTaskWithoutAdditionalCommandOptions('myCustomTask'));

        $command = new Command('SomeCommand');
        $taskManager->configureCommand($command);

        $this->assertEquals(1, count($command->getDefinition()->getOptions()));

        $this->assertEquals(
            'myCustomTask',
            $command->getDefinition()->getOption('myCustomTask')->getName()
        );
    }

    public function testConfigureCommand_TaskWithAdditionalCommandOptions()
    {
        $taskManager = new TaskManager();

        $taskManager->addTask($this->createSimpleTaskWithAdditionalCommandOptions('myCustomTask'));

        $command = new Command('SomeCommand');
        $taskManager->configureCommand($command);

        $this->assertEquals(3, count($command->getDefinition()->getOptions()));

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

        // create task manager
        $taskManager = new TaskManager();

        // add tasks
        $task1 = $this->createSimpleTaskWithoutAdditionalCommandOptions('task1');
        $task1
            ->expects($this->once())
            ->method('run');
        $taskManager->addTask($task1);

        $task2 = $this->createSimpleTaskWithoutAdditionalCommandOptions('task2');
        $task2
            ->expects($this->once())
            ->method('run');
        $taskManager->addTask($task2);

        // execute tasks
        $taskManager->execute(
            $input,
            $output
        );
    }
}