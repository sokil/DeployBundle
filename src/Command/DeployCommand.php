<?php

namespace Sokil\DeployBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Process\Process;

class DeployCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('deploy');
        $this->getApplication();
        $this
            ->getContainer()
            ->get('deploy.task_manager')
            ->configureCommand($this);
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ) {
        $this
            ->getContainer()
            ->get('deploy.task_manager')
            ->execute($input, $output);
    }
}
