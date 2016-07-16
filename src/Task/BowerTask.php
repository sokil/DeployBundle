<?php

namespace Sokil\DeployBundle\Task;

use Sokil\DeployBundle\TaskManager\AbstractTask;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;

class BowerTask extends AbstractTask  implements BundleTaskInterface
{
    public function run()
    {
        $bowerPath = $bundlePath . 'bower.json';

        if (!file_exists($bowerPath)) {
            return true;
        }

        $output->writeln('<' . $this->h2Style . '>Install bower dependencies from ' . $bowerPath . '</>');

        $productionFlag = $environment === 'prod' ? ' --production' : null;

        return $this->runShellCommand(
            'cd ' . $bundlePath . '; bower install' . $productionFlag,
            function() use ($output) {
                $output->writeln('Bower dependencies updated successfully');
            },
            function() use ($output) {
                $output->writeln('<error>Error while updating bower dependencies</error>');
            },
            $output
        );
    }

    public function configureCommand(
        Command $command
    ) {
        $command->addOption(
            'bower',
            null,
            InputOption::VALUE_NONE,
            'Updating bower dependencies'
        );
    }
}