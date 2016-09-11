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

        // setup install method
        if (!isset($options['installMethod'])) {
            $options['installMethod'] = 'install';
        } else {
            $availableInstallMethods = ['install', 'update'];
            if (!in_array($options['installMethod'], $availableInstallMethods)) {
                throw new TaskConfigurationValidateException(
                    sprintf('Composer\'s install method is wrong. Available are %s', implode(',', $availableInstallMethods))
                );
            }

            $options['installMethod'] = $options['installMethod'];
        }

        return $options;
    }

    public function getCommandOptions()
    {
        return [
            'installMethod' => [
                'description' => 'Method of dependency installation: "install" or "update"',
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
        if (empty($commandOptions['installMethod'])) {
            $installMethod = $this->getOption('installMethod');
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
