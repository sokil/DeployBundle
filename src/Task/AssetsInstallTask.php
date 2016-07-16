<?php

namespace Sokil\DeployBundle\Task;

use Sokil\DeployBundle\TaskManager\AbstractTask;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;

class AssetInstallTask extends AbstractTask
{
    public function run()
    {
        $command = $this->getApplication()->find('assets:install');
        return $command->run(
            new ArrayInput(array(
                'command'  => 'assets:install',
                '--env'    => $environment,
            )),
            $output
        );
    }

    public function configureCommand(
        Command $command
    ) {
        $command->addOption(
            'assetsInstall',
            null,
            InputOption::VALUE_NONE,
            'Install bundle assets'
        );
    }
}