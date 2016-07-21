<?php

namespace Sokil\DeployBundle\Task;

use Sokil\DeployBundle\TaskManager\AbstractTask;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;

class AsseticDumpTask implements TaskInterface
{
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
        $command = $this->getApplication()->find('assetic:dump');
        return $command->run(
            new ArrayInput(array(
                'command'  => 'assetic:dump',
                '--env'    => $environment,
            )),
            $output
        );
    }
}