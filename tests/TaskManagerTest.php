<?php

namespace Sokil\DeployBundle;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;

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

    /**
     * @expectedException \Sokil\DeployBundle\Exception\TaskNotFoundException
     * @expectedExceptionMessage Task with alias "someUnexistedTask" not found
     */
    public function testGetNotExistedTask()
    {
        $taskManager = new TaskManager();

        $taskManager->addTask($this->createSimpleTaskWithAdditionalCommandOptions('myCustomTask'));

        $command = new Command('SomeCommand');
        $taskManager->configureCommand($command);

        $taskManager->getTask('someUnexistedTask');
    }

    public function testGetTasks()
    {
        $taskManager = new TaskManager();

        $taskManager->addTask($this->createSimpleTaskWithAdditionalCommandOptions('myCustomTask'));

        $command = new Command('SomeCommand');
        $taskManager->configureCommand($command);

        $tasks = $taskManager->getTasks();

        $this->assertEquals(
            'myCustomTask',
            $tasks['myCustomTask']->getAlias()
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

        $output
            ->expects($this->any())
            ->method('getVerbosity')
            ->will($this->returnValue(OutputInterface::VERBOSITY_NORMAL));

        // create task manager
        $taskManager = new TaskManager();

        // add tasks
        $task1 = $this->createSimpleTaskWithoutAdditionalCommandOptions('task1');
        $task1
            ->expects($this->once())
            ->method('run')
            ->with(
                $this->isType('callable'),
                $this->isType('callable'),
                $this->equalTo('dev'),
                $this->equalTo(OutputInterface::VERBOSITY_NORMAL)
            );
        $taskManager->addTask($task1);

        $task2 = $this->createSimpleTaskWithoutAdditionalCommandOptions('task2');
        $task2
            ->expects($this->once())
            ->method('run')
            ->with(
                $this->isType('callable'),
                $this->isType('callable'),
                $this->equalTo('dev'),
                $this->equalTo(OutputInterface::VERBOSITY_NORMAL)
            );
        $taskManager->addTask($task2);

        // execute tasks
        $taskManager->execute(
            $input,
            $output
        );
    }
}