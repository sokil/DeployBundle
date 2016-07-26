<?php

namespace Sokil\DeployBundle\Command;

use Sokil\DeployBundle\TaskManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DeployCommand extends Command
{
    /**
     * @var TaskManager
     */
    private $taskManager;

    public function __construct(
        $name,
        TaskManager $taskManager
    ) {
        $this->taskManager = $taskManager;

        parent::__construct($name);
    }

    protected function configure()
    {
        $this->taskManager->configureCommand($this);
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ) {
        $this->taskManager->execute($input, $output);
    }
}
