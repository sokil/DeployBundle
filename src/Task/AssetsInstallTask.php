<?php

namespace Sokil\DeployBundle\Task;

use Sokil\DeployBundle\TaskManager\CommandLocator;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\OutputInterface;

class AssetsInstallTask extends AbstractTask
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
        return 'Install bundle assets';
    }

    public function run(
        array $commandOptions,
        $environment,
        $verbosity,
        OutputInterface $output
    ) {
        $command = $this->commandLocator->find('assets:install');
        return $command->run(
            new ArrayInput(array(
                'command'  => 'assets:install',
                '--env'    => $environment,
            )),
            $output
        );
    }
}