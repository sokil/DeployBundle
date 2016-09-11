<?php

namespace Sokil\DeployBundle\Task;

use Sokil\DeployBundle\Exception\TaskConfigurationValidateException;
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
     */
    public function setProcessRunner(ProcessRunner $runner)
    {
        $this->processRunner = $runner;
    }

    public function getDescription()
    {
        return 'Update composer dependencies';
    }

    protected function prepareOptions(array $options)
    {
        // disable composer scripts by default
        if (!isset($options['scripts'])) {
            $options['scripts'] = true;
        } else {
            $options['scripts'] = (bool)$options['scripts'];
        }

        // configure install method
        if (!isset($options['update'])) {
            $options['update'] = false;
        } else {
            $options['update'] = (bool)$options['update'];
        }

        return $options;
    }

    public function getCommandOptions()
    {
        return [
            'update' => [
                'description' => 'Update dependencies instead of install it',
            ]
        ];
    }

    public function run(
        array $commandOptions,
        $environment,
        $verbosity,
        OutputInterface $output
    ) {
        $output->writeln('<' . self::STYLE_H2 . '>Updating composer dependencies</>');

        // get install method
        if (!empty($commandOptions['update']) || $this->getOption('update')) {
            $installMethod = 'update';
        } else {
            $installMethod = 'install';
        }

        $command = 'composer.phar ' . $installMethod . ' --optimize-autoloader --no-interaction';

        // env
        if ($environment !== 'dev') {
            $command .= ' --no-dev';
        }

        // scripts
        if (false === $this->getOption('scripts')) {
            $command .= ' --no-scripts';
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
            $output
        );

        if (!$isSuccessful) {
            throw new TaskExecuteException('Error updating composer dependencies');
        }

        $output->writeln('Composer dependencies updated successfully');
    }
}
