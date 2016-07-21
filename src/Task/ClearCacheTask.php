<?php

namespace Sokil\DeployBundle\Task;

use Sokil\DeployBundle\TaskManager\AbstractTask;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;

class ClearCacheTask extends AbstractTask
{
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
        $command = $this->getApplication()->find('cache:clear');
        return $command->run(
            new ArrayInput(array(
                'command'  => 'cache:clear',
                '--env'    => $environment,
            )),
            $output
        );
    }
}