<?php

namespace Sokil\DeployBundle\Task;

use Sokil\DeployBundle\Exception\TaskExecuteException;
use Sokil\DeployBundle\TaskManager\CommandLocator;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\OutputInterface;

class ClearCacheTask extends AbstractTask
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
        return 'Clear cache';
    }

    public function run(
        array $commandOptions,
        $environment,
        $verbosity,
        OutputInterface $output
    ) {
        $command = $this->commandLocator->find('cache:clear');
        $isSuccessful = $command->run(
            new ArrayInput(array(
                'command'  => 'cache:clear',
                '--env'    => $environment,
            )),
            $output
        );

        if (!$isSuccessful) {
            throw new TaskExecuteException('Error clearing cache');
        }
    }
}