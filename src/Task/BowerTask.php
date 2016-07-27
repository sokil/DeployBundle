<?php

namespace Sokil\DeployBundle\Task;

use Sokil\DeployBundle\Exception\TaskConfigurationValidateException;
use Sokil\DeployBundle\Exception\TaskExecuteException;
use Sokil\DeployBundle\TaskManager\ProcessRunner;
use Sokil\DeployBundle\TaskManager\ResourceLocator;
use Symfony\Component\Console\Output\OutputInterface;

class BowerTask extends AbstractTask implements
    ResourceAwareTaskInterface,
    ProcessRunnerAwareTaskInterface
{
    /**
     * @var ResourceLocator
     */
    private $resourceLocator;

    /**
     * @var ProcessRunner
     */
    private $processRunner;

    public function setResourceLocator(ResourceLocator $locator)
    {
        $this->resourceLocator = $locator;
        return $this;
    }

    /**
     * @param ProcessRunner $runner
     */
    public function setProcessRunner(ProcessRunner $runner)
    {
        $this->processRunner = $runner;
    }

    public function getDescription()
    {
        return 'Updating bower dependencies';
    }

    /**
     * Prepare task options: check values and set default values
     *
     * @param array $options configuration
     * @throws TaskConfigurationValidateException
     * @return array validated options with default values on empty params
     */
    protected function prepareOptions(array $options)
    {
        // bundles list
        if (empty($options['bundles']) || !is_array($options['bundles'])) {
            throw new TaskConfigurationValidateException('Bundles not configured for bower');
        }

        $options['bundles'] = array_keys(array_filter($options['bundles']));

        return $options;
    }

    /**
     * @param array $commandOptions
     * @param $environment
     * @param $verbosity
     * @param OutputInterface $output
     * @return bool
     * @throws TaskExecuteException
     */
    public function run(
        array $commandOptions,
        $environment,
        $verbosity,
        OutputInterface $output
    ) {
        foreach ($this->getOption('bundles') as $bundleName) {
            // get bundle path
            $bundlePath = $this->resourceLocator->locateResource('@' . $bundleName);

            // check path to bower
            $bowerPath = $bundlePath . 'bower.json';
            if (!file_exists($bowerPath)) {
                return true;
            }

            // execute
            $output->writeln('<' . $this->h2Style . '>Install bower dependencies from ' . $bowerPath . '</>');

            $productionFlag = $environment === 'prod' ? ' --production' : null;

            $isSuccessful = $this->processRunner->run(
                'cd ' . $bundlePath . '; bower install' . $productionFlag,
                $environment,
                $verbosity,
                $output
            );

            if (!$isSuccessful) {
                throw new TaskExecuteException('Error updating bower dependencies for bundle ' . $bundleName);
            }

            $output->writeln('Bower dependencies updated successfully for bundle ' . $bundleName);
        }
    }
}