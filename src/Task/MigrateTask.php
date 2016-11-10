<?php

namespace Sokil\DeployBundle\Task;

use Sokil\DeployBundle\Exception\TaskExecuteException;
use Sokil\DeployBundle\TaskManager\CommandLocator;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\OutputInterface;

class MigrateTask extends AbstractTask
    implements CommandAwareTaskInterface
{
    /**
     * @var CommandLocator
     */
    private $commandLocator;

    /**
     * @param CommandLocator $locator
     */
    public function setCommandLocator(CommandLocator $locator)
    {
        $this->commandLocator = $locator;
    }

    public function getDescription()
    {
        return 'Migrate datbase';
    }

    public function run(
        array $commandOptions,
        $environment,
        $verbosity,
        OutputInterface $output
    ) {
        $command = $this->commandLocator->find('doctrine:migrations:migrate');

        $input = new ArrayInput(array(
            'command' => 'doctrine:migrations:migrate',
        ));

        $input->setInteractive(false);

        $exitCode = $command->run(
            $input,
            $output
        );

        if (0 !== $exitCode) {
            throw new TaskExecuteException('Error migrating database');
        }
    }
}