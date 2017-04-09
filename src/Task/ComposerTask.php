<?php

namespace Sokil\DeployBundle\Task;

use Sokil\DeployBundle\Exception\TaskConfigurationValidateException;
use Sokil\DeployBundle\Exception\TaskExecuteException;
use Sokil\DeployBundle\TaskManager\ProcessRunner;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ComposerTask extends AbstractTask implements
    ProcessRunnerAwareTaskInterface
{
    /**
     * @var ProcessRunner
     */
    private $processRunner;

    /**
     * @var bool
     */
    private $isScriptExecutionAllowed = true;

    /**
     * @var bool
     */
    private $isUpdateRequired = false;

    /**
     * @var string
     */
    private $pathToComposer = 'composer.phar';

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

    /**
     * @return array
     */
    public function getCommandOptionDefinitions()
    {
        return [
            'update' => [
                'description' => 'Update dependencies instead of install it',
                'mode' => InputOption::VALUE_NONE,
            ]
        ];
    }

    /**
     * @param array $options
     */
    protected function configure(array $options)
    {
        // disable composer scripts by default
        if (isset($options['scripts'])) {
            $this->isScriptExecutionAllowed = (bool)$options['scripts'];
        }

        // configure install method
        if (isset($options['update'])) {
            $this->isUpdateRequired = (bool)$options['update'];
        }

        // configure path to composer
        if (isset($options['path'])) {
            $this->pathToComposer = $options['path'];
        }
    }

    public function run(
        array $commandOptions,
        $environment,
        $verbosity,
        OutputInterface $output
    ) {
        $output->writeln('<' . self::STYLE_H2 . '>Updating composer dependencies</>');

        // get install method
        if (!empty($commandOptions['update']) || $this->isUpdateRequired) {
            $installMethod = 'update';
        } else {
            $installMethod = 'install';
        }
        
        // command
        $command = $this->pathToComposer . ' ' . $installMethod . ' --optimize-autoloader --no-interaction';

        // env
        if ($environment !== 'dev') {
            $command .= ' --no-dev';
        }

        // scripts
        if (false === $this->isScriptExecutionAllowed) {
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
