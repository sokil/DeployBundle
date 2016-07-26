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

        $isSuccessful = $command->run(
            new ArrayInput(array(
                'command' => 'doctrine:migrations:migrate',
                '--no-interaction' => true,
            )),
            $output
        );

        if (!$isSuccessful) {
            throw new TaskExecuteException('Error updating bower dependencies for bundle ' . $bundleName);
        }
    }
}