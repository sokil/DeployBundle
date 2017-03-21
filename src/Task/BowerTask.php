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

    /**
     * @var array
     */
    private $bundleList;

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
     *
     * @throws TaskConfigurationValidateException
     */
    protected function configure(array $options)
    {
        // bundles list
        if (empty($options['bundles']) || !is_array($options['bundles'])) {
            throw new TaskConfigurationValidateException('Bundles not configured for bower');
        }

        $this->bundleList = array_keys(array_filter($options['bundles']));
    }

    /**
     * @param string $bundleName
     *
     * @return string
     *
     * @throws TaskConfigurationValidateException
     */
    protected function getBowerfilePath($bundleName)
    {
        // get bundle path
        $bundlePath = $this->resourceLocator->locateResource('@' . $bundleName);
        // find path to Gruntfile
        $bowerfilePath = $bundlePath . 'bower.json';
        if (!file_exists($bowerfilePath)) {
            throw new TaskConfigurationValidateException('Bundle "' . $bundleName . '" configured for running bower task but bower.json not found at "' . $bundlePath . '"');
        }
        return $bowerfilePath;
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
        // get path list to Gruntfile
        $bowerFilePathList = [];
        foreach ($this->bundleList as $bundleName) {
            // store bundle path
            $bowerFilePathList[$bundleName] = $this->getBowerfilePath($bundleName);
        }

        foreach ($bowerFilePathList as $bundleName => $bowerPath) {
            // execute
            $output->writeln('<' . self::STYLE_H2 . '>Install bower dependencies from ' . $bowerPath . '</>');

            $productionFlag = $environment === 'prod' ? ' --production' : null;

            $isSuccessful = $this->processRunner->run(
                'cd ' . dirname($bowerPath) . '; bower install --allow-root' . $productionFlag,
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
