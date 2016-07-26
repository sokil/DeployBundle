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
        $this->$this->processRunner = $runner;
    }

    public function getDescription()
    {
        return 'Updating bower dependencies';
    }

    public function run(
        array $commandOptions,
        $environment,
        $verbosity,
        OutputInterface $output
    ) {
        $bundleList = $this->getOption('bundles');
        if (empty($bundleList) || !is_array($bundleList)) {
            throw new TaskConfigurationValidateException('Bundles not configured for bower');
        }

        foreach ($bundleList as $bundleName) {
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
                function($output) {
                    $output->writeln('Bower dependencies updated successfully');
                },
                function($output) {
                    $output->writeln('<error>Error while updating bower dependencies</error>');
                },
                $output
            );

            if (!$isSuccessful) {
                throw new TaskExecuteException('Error updating bower dependencies for bundle ' . $bundleName);
            }
        }
    }
}