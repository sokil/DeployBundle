<?php

namespace Sokil\DeployBundle\Task;

use Sokil\DeployBundle\TaskManager\AbstractTask;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;

class MigrateTask extends AbstractTask
{
    public function getDescription()
    {
        return 'Migrate datbase';
    }

    public function run(
        callable $input,
        callable $output,
        $environment,
        $verbosity
    ) {
        $command = $this->getApplication()->find('doctrine:migrations:migrate');

        return $command->run(
            new ArrayInput(array(
                'command' => 'doctrine:migrations:migrate',
                '--no-interaction' => true,
            )),
            $output
        );
    }
}