<?php

namespace Sokil\DeployBundle\Task;

use Sokil\DeployBundle\Task\CommandAwareTaskInterface;
use Sokil\DeployBundle\TaskManager\CommandLocator;
use Symfony\Component\Console\Output\OutputInterface;

class AsseticDumpTask extends AbstractTask
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
        return 'Dump assetic assets';
    }

    public function run(
        array $commandOptions,
        $environment,
        $verbosity,
        OutputInterface $output
    ) {
        $command = $this->commandLocator->find('assetic:dump');
        return $command->run(
            new ArrayInput(array(
                'command'  => 'assetic:dump',
                '--env'    => $environment,
            )),
            $output
        );
    }
}