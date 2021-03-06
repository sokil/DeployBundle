<?php

/**
 * This file is part of the DeployBundle package.
 *
 * (c) Dmytro Sokil <dmytro.sokil@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sokil\DeployBundle\Task;

use Sokil\DeployBundle\Exception\TaskConfigurationValidateException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractTask implements TaskInterface
{
    const STYLE_H1 = 'fg=black;bg=cyan';
    const STYLE_H2 = 'fg=black;bg=yellow';

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
     * @param string $alias
     * @param array $options
     */
    public function __construct($alias)
    {
        // set alias
        $this->alias = $alias;
    }

    /**
     * @return string
     */
    abstract public function getDescription();

    /**
     * Configuration of CLI options
     *
     * Allowed keys:
     *  - description: help message of configured CLI option
     *  - default: default value of CLI option
     *  - mode: one of InputOption::VALUE_*
     *  - shortcut: The shortcuts, can be null, a string of shortcuts delimited by | or an array of shortcuts
     *
     * For example:
     * [
     *     'update' => [
     *         'description' => 'Update dependencies instead of install it',
     *         'mode' => InputOption::VALUE_OPTIONAL,
     *         'default' => '42',
     *         'shortcut' => 'u',
     *     ],
     * ]
     *
     * @return array command options with parameters
     */
    public function getCommandOptionDefinitions()
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
     *
     * @throws TaskConfigurationValidateException
     */
    public function configure(array $options)
    {
    }

    /**
     * @param array $commandOptions cli options
     * @param string $environment
     * @param int $verbosity The current level of verbosity (one of the OutputInterface::VERBOSITY_* constants)
     * @param OutputInterface $output
     */
    abstract public function run(
        array $commandOptions,
        $environment,
        $verbosity,
        OutputInterface $output
    );
}
