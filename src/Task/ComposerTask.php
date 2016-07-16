<?php

namespace Sokil\DeployBundle\Task;

use Sokil\DeployBundle\TaskManager\AbstractTask;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;

class ComposerTask extends AbstractTask
{
    public function run()
    {
        $this->output->writeln('<' . $this->h2Style . '>Updating composer dependencies</>');

        $command = 'composer.phar update --optimize-autoloader --no-interaction';

        if ($environment !== 'dev') {
            $command .= ' --no-dev';
        }

        // verbosity
        $verbosity = $this->output->getVerbosity();
        switch ($verbosity) {
            case OutputInterface::VERBOSITY_VERBOSE:
                $command .= ' -v';
                break;
            case OutputInterface::VERBOSITY_VERY_VERBOSE:
                $command .= ' -vv';
                break;
            case OutputInterface::VERBOSITY_DEBUG:
                $command .= ' -vvv';
                break;
        }

        return $this->runShellCommand(
            $command,
            function() {
                $this->output->writeln('Composer dependencies updated successfully');
            },
            function() {
                $this->output->writeln('<error>Error updating composer dependencies</error>');
            },
            $this->output
        );
    }

    public function configureCommand(
        Command $command
    ) {
        $command->addOption(
            'composer',
            null,
            InputOption::VALUE_NONE,
            'Update composer dependencies'
        );
    }
}