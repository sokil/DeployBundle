<?php

namespace Sokil\DeployBundle\Task;

use Sokil\DeployBundle\TaskManager\AbstractTask;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;

class AssetsInstallTask extends AbstractTask
{
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
        $command = $this->getApplication()->find('assets:install');
        return $command->run(
            new ArrayInput(array(
                'command'  => 'assets:install',
                '--env'    => $environment,
            )),
            $output
        );
    }
}