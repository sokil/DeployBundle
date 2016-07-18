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
}