<?php

namespace Sokil\DeployBundle\Task;

use Symfony\Component\Console\Output\OutputInterface;

interface TaskInterface
{
    public function __construct(
        $alias,
        array $options
    );

    public function getAlias();

    public function getDescription();

    public function getOptions();

    public function getCommandOptions();

    public function run(
        array $commandOptions,
        $environment,
        $verbosity,
        OutputInterface $output
    );
}