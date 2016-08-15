<?php

namespace Sokil\DeployBundle\Task;

use Sokil\DeployBundle\Exception\TaskConfigurationValidateException;
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
        // set alias
        $this->alias = $alias;

        // set options
        $this->options = $this->prepareOptions($options);
    }

    public function getDescription()
    {
        return 'Description not specified';
    }

    /**
     * Configuration of CLI options
     * Allowed keys:
     *  - description - help message of configured CLI option
     *  - default - default vlue of CLI option
     *
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
     * Prepare task options configured in bundle`s config: check values and set default values
     *
     * @param array $options configuration
     * @throws TaskConfigurationValidateException
     * @return array validated options with default values on empty params
     */
    protected function prepareOptions(array $options)
    {
        return $options;
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