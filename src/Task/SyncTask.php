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

class SyncTask extends AbstractTask
{
    /**
     * Get console command description
     * @return mixed
     */
    public function getDescription()
    {
        return 'Sync files between servers';
    }

    /**
     * Run task
     *
     * @param array $commandOptions
     * @param $environment
     * @param $verbosity
     * @param OutputInterface $output
     */
    public function run(
        array $commandOptions,
        $environment,
        $verbosity,
        OutputInterface $output
    ) {

    }
}
