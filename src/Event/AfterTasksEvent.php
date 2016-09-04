<?php

namespace Sokil\DeployBundle\Event;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\Event;

class AfterTasksEvent extends Event
{
    const name = 'afterTasks';

    /**
     * @var string
     */
    private $environment;

    /**
     * @var int
     */
    private $verbosity;

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @param string $environment
     * @param int $verbosity
     * @param OutputInterface $output
     */
    public function __construct($environment, $verbosity, OutputInterface $output)
    {
        $this->environment = $environment;
        $this->verbosity = $verbosity;
        $this->output = $output;
    }

    /**
     * @return string
     */
    public function getEnvironment()
    {
        return $this->environment;
    }

    /**
     * @return int
     */
    public function getVerbosity()
    {
        return $this->verbosity;
    }

    /**
     * @return OutputInterface
     */
    public function getOutput()
    {
        return $this->output;
    }
}