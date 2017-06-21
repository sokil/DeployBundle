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

use Symfony\Component\Console\Output\OutputInterface;

interface TaskInterface
{
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
     * Configure console command options
     * @return mixed
     */
    public function getCommandOptionDefinitions();

    /**
     * Run task
     *
     * @param array $commandOptions
     * @param string $environment
     * @param $verbosity
     * @param OutputInterface $output
     */
    public function run(
        array $commandOptions,
        $environment,
        $verbosity,
        OutputInterface $output
    );
}
