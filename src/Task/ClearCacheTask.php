<?php

namespace Sokil\DeployBundle\Task;

use Sokil\DeployBundle\TaskManager\AbstractTask;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;

class ClearCacheTask extends AbstractTask
{
    public function configureCommand(
        Command $command
    ) {
        $command->addOption(
            'clearCache',
            null,
            InputOption::VALUE_NONE,
            'ClearCache'
        );
    }

    public function run()
    {

    }
}