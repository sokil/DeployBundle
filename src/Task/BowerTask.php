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

    protected function getBowerfilePath($bundleName)
    {
        // get bundle path
        $bundlePath = $this->resourceLocator->locateResource('@' . $bundleName);
        // find path to Gruntfile
        $bowefilePath = $bundlePath . 'bower.json';
        if (!file_exists($bowefilePath)) {
            throw new TaskConfigurationValidateException('Bundle "' . $bundleName . '" configured for running bower task but bower.json not found at "' . $bundlePath . '"');
        }
        return $bowefilePath;
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
        $bundleTasksList = $this->getOption('bundles');

        // get path list to Gruntfile
        $bowerfilePathList = [];
        foreach ($bundleTasksList as $bundleName => $tasks) {
            // store bundle path
            $bowerfilePathList[$bundleName] = $this->getGruntfilePath($bundleName);
        }

        foreach ($bowerfilePathList as $bowerPath) {
            // execute
            $output->writeln('<' . $this->h2Style . '>Install bower dependencies from ' . $bowerPath . '</>');

            $productionFlag = $environment === 'prod' ? ' --production' : null;

            $isSuccessful = $this->processRunner->run(
                'cd ' . dirname($bowerPath) . '; bower install' . $productionFlag,
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