<?php

namespace Sokil\DeployBundle\Task;

use Symfony\Component\Console\Output\OutputInterface;

interface TaskInterface
{
    /**
     * Configure task alias and task options
     *
     * TaskInterface constructor.
     * @param $alias
     * @param array $options
     */
    public function __construct(
        $alias,
        array $options
    );

    /**
     * Get console command alias
     * @return mixed
     */
    public function getAlias();

    /**
     * Get console command description
     * @return mixed
     */
    public function getDescription();

    /**
     * Get task options
     * @return mixed
     */
    public function getOptions();

    /**
     * Configure console command options
     * @return mixed
     */
    public function getCommandOptions();

    /**
     * Run task
     * @param array $commandOptions
     * @param $environment
     * @param $verbosity
     * @param OutputInterface $output
     * @return mixed
     */
    public function run(
        array $commandOptions,
        $environment,
        $verbosity,
        OutputInterface $output
    );
}