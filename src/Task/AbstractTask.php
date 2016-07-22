<?php

namespace Sokil\DeployBundle\Task;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractTask implements TaskInterface
{
    protected $h1Style = 'fg=black;bg=cyan';
    protected $h2Style = 'fg=black;bg=yellow';

    /**
     * @var InputInterface
     */
    protected $input;

    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * @var string
     */
    private $alias;

    /**
     * @var array
     */
    private $options;

    public function __construct(
        $alias,
        array $options
    ) {
        $this->alias = $alias;
        $this->options = $options;
    }

    public function getDescription()
    {
        return 'Description not specified';
    }

    /**
     * @return array command options with parameters
     */
    public function getCommandOptions()
    {
        return [];
    }

    /**
     * @return string
     */
    public function getAlias()
    {
        return $this->alias;
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @param string $name
     * @param mixed|null $default
     * @return mixed|null
     */
    public function getOption($name, $default = null)
    {
        return isset($this->options[$name]) ? $this->options[$name] : $default;
    }

    /**
     * @param array $commandOptions
     * @param $environment
     * @param $verbosity
     * @param callable $outputWriter
     * @return mixed
     */
    abstract public function run(
        array $commandOptions,
        $environment,
        $verbosity,
        OutputInterface $output
    );
}