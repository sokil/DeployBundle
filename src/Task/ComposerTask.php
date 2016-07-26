<?php

namespace Sokil\DeployBundle\Task;

use Sokil\DeployBundle\Exception\TaskExecuteException;
use Sokil\DeployBundle\TaskManager\ProcessRunner;
use Symfony\Component\Console\Output\OutputInterface;

class ComposerTask extends AbstractTask implements
    ProcessRunnerAwareTaskInterface
{
    /**
     * @var ProcessRunner
     */
    private $processRunner;

    /**
     * @param ProcessRunner $runner
     * @return BowerTask
     */
    public function setProcessRunner(ProcessRunner $runner)
    {
        $this->processRunner = $runner;
        return $this;
    }

    public function getDescription()
    {
        return 'Update composer dependencies';
    }

    public function run(
        array $commandOptions,
        $environment,
        $verbosity,
        OutputInterface $output
    ) {
        $output->writeln('<' . $this->h2Style . '>Updating composer dependencies</>');

        $command = 'composer.phar update --optimize-autoloader --no-interaction';

        if ($environment !== 'dev') {
            $command .= ' --no-dev';
        }

        // verbosity
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

        $isSuccessful = $this->processRunner->run(
            $command,
            $environment,
            $verbosity,
            function($output) {
                $output->writeln('Composer dependencies updated successfully');
            },
            function($output) {
                $output->writeln('<error>Error updating composer dependencies</error>');
            },
            $output
        );

        if (!$isSuccessful) {
            throw new TaskExecuteException('Error updating bower dependencies for bundle ' . $bundleName);
        }
    }
}