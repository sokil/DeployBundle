<?php

namespace Sokil\DeployBundle\Task;

use Sokil\DeployBundle\TaskManager\AbstractTask;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;

class NpmTask extends AbstractTask implements BundleTaskInterface
{
    public function run()
    {
        $npmPath = $bundlePath . 'package.json';

        if (!file_exists($npmPath)) {
            return true;
        }

        $output->writeln('<' . $this->h2Style . '>Install npm dependencies from ' . $npmPath . '</>');

        $productionFlag = $environment === 'prod' ? ' --production' : null;

        return $this->runShellCommand(
            'cd ' . $bundlePath . '; npm install' . $productionFlag,
            function() use ($output) {
                $output->writeln('Npm dependencies updated successfully');
            },
            function() use ($output) {
                $output->writeln('<error>Error while updating Npm dependencies</error>');
            },
            $output
        );
    }

    public function configureCommand(
        Command $command
    ) {
        $command->addOption(
            'npm',
            null,
            InputOption::VALUE_NONE,
            'Updating npm dependencies'
        );
    }
}