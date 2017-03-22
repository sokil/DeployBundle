<?php

namespace Sokil\DeployBundle\TaskManager;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\CommandNotFoundException;

class CommandLocator
{
    /**
     * @var Application
     */
    private $application;

    public function setApplication(Application $application)
    {
        $this->application = $application;
    }

    /**
     * Finds a command by name or alias.
     *
     * Contrary to get, this command tries to find the best
     * match if you give it an abbreviation of a name or alias.
     *
     * @param string $name A command name or a command alias
     *
     * @return Command A Command instance
     *
     * @throws CommandNotFoundException When command name is incorrect or ambiguous
     */
    public function find($name)
    {
        return $this->application->find($name);
    }
}
